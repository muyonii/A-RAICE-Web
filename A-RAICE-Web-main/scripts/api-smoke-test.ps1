param(
    [string]$BaseUrl = 'http://localhost:8000',
    [string]$VitUrl
)

$ErrorActionPreference = 'Stop'
$script:Passed = 0
$script:Failed = 0
$script:FarmerToken = $null
$script:Farmer2Token = $null
$script:LguToken = $null
$script:FarmerScanId = $null
$script:FarmerProfileId = $null
$script:LguScanId = $null
$script:FarmerAlertId = $null
$BaseUrl = $BaseUrl.TrimEnd('/')
$ProjectRoot = Split-Path -Parent $PSScriptRoot
$SampleImage = Join-Path $ProjectRoot 'public\images\stem_rot.jpg'

function Write-Check {
    param(
        [Parameter(Mandatory = $true)][string]$Name,
        [Parameter(Mandatory = $true)][scriptblock]$Action
    )

    try {
        $null = & $Action
        Write-Host "PASS $Name" -ForegroundColor Green
        $script:Passed++
    }
    catch {
        Write-Host "FAIL $Name - $($_.Exception.Message)" -ForegroundColor Red
        $script:Failed++
    }
}

function Read-VitUrl {
    if (-not [string]::IsNullOrWhiteSpace($VitUrl)) {
        return $VitUrl.TrimEnd('/')
    }

    if (-not [string]::IsNullOrWhiteSpace($env:VIT_API_URL)) {
        return $env:VIT_API_URL.TrimEnd('/')
    }

    $envFile = Join-Path $ProjectRoot '.env'
    if (Test-Path $envFile) {
        $line = Get-Content $envFile | Where-Object { $_ -match '^\s*VIT_API_URL\s*=' } | Select-Object -First 1
        if ($line -match '^\s*VIT_API_URL\s*=\s*(.*?)\s*$') {
            return $Matches[1].Trim().Trim('"').Trim("'").TrimEnd('/')
        }
    }

    return $null
}

function Wait-ForVit {
    param([Parameter(Mandatory = $true)][string]$Url)

    if ([string]::IsNullOrWhiteSpace($Url)) {
        throw 'VIT_API_URL is not set. Pass -VitUrl or configure it in the project .env file.'
    }

    $timer = [System.Diagnostics.Stopwatch]::StartNew()
    $lastError = 'No response received.'

    while ($timer.Elapsed.TotalSeconds -lt 60) {
        try {
            $null = Invoke-RestMethod -Method Get -Uri "$Url/health" -TimeoutSec 8 -UseBasicParsing
            return
        }
        catch {
            $lastError = $_.Exception.Message
            $remaining = 60 - [int]$timer.Elapsed.TotalSeconds
            if ($remaining -gt 0) {
                Start-Sleep -Seconds ([Math]::Min(5, $remaining))
            }
        }
    }

    throw "ViT health endpoint was unreachable for 60 seconds ($Url/health). The service may be waking from sleep. Last error: $lastError"
}

function Invoke-Api {
    param(
        [Parameter(Mandatory = $true)][string]$Method,
        [Parameter(Mandatory = $true)][string]$Path,
        [hashtable]$Headers = @{ Accept = 'application/json' },
        [object]$Body
    )

    $parameters = @{
        Method = $Method
        Uri = "$BaseUrl$Path"
        Headers = $Headers
        UseBasicParsing = $true
        TimeoutSec = 300
    }

    if ($null -ne $Body) {
        $parameters.ContentType = 'application/json'
        $parameters.Body = ConvertTo-Json -InputObject $Body -Depth 10 -Compress
    }

    try {
        $response = Invoke-RestMethod @parameters
        return @{ Status = 200; Data = $response }
    }
    catch {
        $httpResponse = $_.Exception.Response
        if ($null -eq $httpResponse) {
            throw
        }

        $status = [int]$httpResponse.StatusCode
        $responseBody = ''
        try {
            $reader = New-Object System.IO.StreamReader($httpResponse.GetResponseStream())
            $responseBody = $reader.ReadToEnd()
            $reader.Dispose()
        }
        catch {
            $responseBody = ''
        }

        $data = $null
        if (-not [string]::IsNullOrWhiteSpace($responseBody)) {
            try {
                $data = ConvertFrom-Json -InputObject $responseBody
            }
            catch {
                $data = $responseBody
            }
        }

        return @{ Status = $status; Data = $data }
    }
}

function Invoke-MultipartUpload {
    param(
        [Parameter(Mandatory = $true)][string]$Token,
        [switch]$IncludeImage,
        [Nullable[int]]$FarmerId
    )

    $curlArguments = @(
        '--silent',
        '--show-error',
        '--connect-timeout', '15',
        '--max-time', '300',
        '-X', 'POST',
        "$BaseUrl/api/scans",
        '-H', "Authorization: Bearer $Token",
        '-H', 'Accept: application/json'
    )

    if ($IncludeImage) {
        $curlArguments += @('-F', "image=@$SampleImage")
    }
    if ($null -ne $FarmerId) {
        $curlArguments += @('-F', "farmer_id=$FarmerId")
    }
    $curlArguments += @('--write-out', "`n%{http_code}")

    $output = @(& curl.exe @curlArguments 2>&1)
    $curlExitCode = $LASTEXITCODE
    $text = $output -join "`n"
    if ($curlExitCode -ne 0) {
        throw "curl.exe failed with exit code $curlExitCode. $text"
    }

    $separator = $text.LastIndexOf("`n")
    if ($separator -lt 0) {
        throw "Could not read HTTP status from curl.exe output: $text"
    }

    $bodyText = $text.Substring(0, $separator).Trim()
    $statusText = $text.Substring($separator + 1).Trim()
    $data = $null
    if (-not [string]::IsNullOrWhiteSpace($bodyText)) {
        try {
            $data = ConvertFrom-Json -InputObject $bodyText
        }
        catch {
            throw "API returned a non-JSON response: $bodyText"
        }
    }

    return @{ Status = [int]$statusText; Data = $data }
}

function Get-AuthHeaders {
    param([string]$Token)

    return @{
        Accept = 'application/json'
        Authorization = "Bearer $Token"
    }
}

function Get-LoginToken {
    param([string]$Email, [string]$Password)

    $response = Invoke-Api -Method Post -Path '/api/login' -Body @{
        email = $Email
        password = $Password
    }
    if ($response.Status -ne 200 -or [string]::IsNullOrWhiteSpace($response.Data.token)) {
        throw "Login returned HTTP $($response.Status)."
    }

    return [string]$response.Data.token
}

Write-Host "API base URL: $BaseUrl"
Write-Host "Sample image: $SampleImage"

Write-Check 'sample image is available' {
    if (-not (Test-Path $SampleImage)) {
        throw "Missing sample image: $SampleImage"
    }
}

$VitUrl = Read-VitUrl
Write-Check 'ViT health endpoint reachable (retry window: 60 seconds)' {
    Wait-ForVit -Url $VitUrl
}
$script:VitReachable = ($script:Failed -eq 0)

Write-Check 'farmer login' {
    $script:FarmerToken = Get-LoginToken 'api-farmer@test.local' 'password'
}

if ($script:VitReachable -and $script:FarmerToken) {
    Write-Check 'farmer submits scan with sample image' {
        $response = Invoke-MultipartUpload -Token $script:FarmerToken -IncludeImage
        if ($response.Status -ne 201) {
            throw "Scan submission returned HTTP $($response.Status)."
        }
        if ($response.Data.status -ne 'completed') {
            throw "Scan status is '$($response.Data.status)', expected 'completed'."
        }
        $script:FarmerScanId = [int]$response.Data.id
    }
}
else {
    Write-Check 'farmer submits scan with sample image' {
        throw 'Skipped because the ViT service or farmer login is unavailable.'
    }
}

if ($script:FarmerToken -and $script:FarmerScanId) {
    Write-Check 'farmer list contains submitted scan' {
        $response = Invoke-Api -Method Get -Path '/api/scans' -Headers (Get-AuthHeaders $script:FarmerToken)
        if ($response.Status -ne 200 -or -not ($response.Data.data | Where-Object { $_.id -eq $script:FarmerScanId })) {
            throw "Scan $script:FarmerScanId was not found in farmer list (HTTP $($response.Status))."
        }
    }

    Write-Check 'farmer can read submitted scan detail' {
        $response = Invoke-Api -Method Get -Path "/api/scans/$script:FarmerScanId" -Headers (Get-AuthHeaders $script:FarmerToken)
        if ($response.Status -ne 200 -or $response.Data.id -ne $script:FarmerScanId) {
            throw "Detail request returned HTTP $($response.Status)."
        }
        if ($null -ne $response.Data.disease) {
            if (-not ($response.Data.disease.PSObject.Properties.Name -contains 'treatments')) {
                throw 'Scan detail disease object is missing treatments.'
            }
            foreach ($treatment in @($response.Data.disease.treatments)) {
                if ($null -eq $treatment.id -or [string]::IsNullOrWhiteSpace($treatment.title) -or
                    $null -eq $treatment.description -or [string]::IsNullOrWhiteSpace($treatment.type)) {
                    throw 'Scan detail contains a treatment without id, title, description, or type.'
                }
            }
        }
    }
}
else {
    Write-Check 'farmer list contains submitted scan' { throw 'Skipped because farmer scan submission failed.' }
    Write-Check 'farmer can read submitted scan detail' { throw 'Skipped because farmer scan submission failed.' }
}

if ($script:FarmerToken) {
    Write-Check 'farmer unread alerts include seeded outbreak alert' {
        $response = Invoke-Api -Method Get -Path '/api/alerts?unread=1' -Headers (Get-AuthHeaders $script:FarmerToken)
        $alert = $response.Data.data | Where-Object {
            $_.disease -eq 'API Smoke Test Disease' -and
            $_.municipality -eq 'balanga' -and
            $null -eq $_.read_at
        } | Select-Object -First 1
        if ($response.Status -ne 200 -or $null -eq $alert) {
            throw "Seeded unread outbreak alert not found (HTTP $($response.Status)). Run ApiTestSeeder first."
        }
        $script:FarmerAlertId = [int]$alert.id
    }

    if ($script:FarmerAlertId) {
        Write-Check 'farmer marks own alert read' {
            $response = Invoke-Api -Method Post -Path "/api/alerts/$script:FarmerAlertId/read" -Headers (Get-AuthHeaders $script:FarmerToken)
            if ($response.Status -ne 200 -or $null -eq $response.Data.read_at) {
                throw "Mark read returned HTTP $($response.Status) or no read_at value."
            }
            $script:FirstAlertReadAt = [string]$response.Data.read_at
        }

        if ($script:FirstAlertReadAt) {
            Start-Sleep -Seconds 1
            Write-Check 'marking an already-read alert is idempotent' {
                $response = Invoke-Api -Method Post -Path "/api/alerts/$script:FarmerAlertId/read" -Headers (Get-AuthHeaders $script:FarmerToken)
                if ($response.Status -ne 200 -or [string]$response.Data.read_at -ne $script:FirstAlertReadAt) {
                    throw 'Repeated read changed the original read_at timestamp.'
                }
            }

            Write-Check 'read alert no longer appears in unread filter' {
                $response = Invoke-Api -Method Get -Path '/api/alerts?unread=1' -Headers (Get-AuthHeaders $script:FarmerToken)
                if ($response.Status -ne 200 -or ($response.Data.data | Where-Object { $_.id -eq $script:FarmerAlertId })) {
                    throw "Read alert still appears in unread results or request returned HTTP $($response.Status)."
                }
            }
        }
        else {
            Write-Check 'marking an already-read alert is idempotent' { throw 'Skipped because marking the alert read failed.' }
            Write-Check 'read alert no longer appears in unread filter' { throw 'Skipped because marking the alert read failed.' }
        }
    }
    else {
        Write-Check 'farmer marks own alert read' { throw 'Skipped because the seeded alert is missing.' }
        Write-Check 'marking an already-read alert is idempotent' { throw 'Skipped because the seeded alert is missing.' }
        Write-Check 'read alert no longer appears in unread filter' { throw 'Skipped because the seeded alert is missing.' }
    }
}
else {
    Write-Check 'farmer unread alerts include seeded outbreak alert' { throw 'Skipped because farmer login failed.' }
    Write-Check 'farmer marks own alert read' { throw 'Skipped because farmer login failed.' }
    Write-Check 'marking an already-read alert is idempotent' { throw 'Skipped because farmer login failed.' }
    Write-Check 'read alert no longer appears in unread filter' { throw 'Skipped because farmer login failed.' }
}

if ($script:FarmerToken) {
    Write-Check 'farmer map returns all summaries and only own farm details' {
        $response = Invoke-Api -Method Get -Path '/api/map' -Headers (Get-AuthHeaders $script:FarmerToken)
        $municipalities = @($response.Data.municipalities)
        $farm = @($response.Data.farms) | Select-Object -First 1
        $invalidMunicipality = $municipalities | Where-Object {
            [string]::IsNullOrWhiteSpace($_.slug) -or
            -not ($_.PSObject.Properties.Name -contains 'severity') -or
            $_.PSObject.Properties.Name.Count -ne 2
        } | Select-Object -First 1
        if ($response.Status -ne 200 -or $municipalities.Count -ne 12 -or $null -ne $invalidMunicipality) {
            throw "Map summaries are invalid or incomplete (HTTP $($response.Status))."
        }
        if (@($response.Data.outbreaks | Where-Object { $_.municipality -ne 'balanga' }).Count -gt 0) {
            throw 'Farmer map exposed outbreak details outside Balanga.'
        }
        if (@($response.Data.farms).Count -ne 1 -or $farm.municipality -ne 'balanga' -or
            $farm.PSObject.Properties.Name -contains 'name' -or
            $farm.PSObject.Properties.Name -contains 'contact_number' -or
            $null -eq $farm.scan.id -or $null -eq $farm.scan.disease -or $null -eq $farm.scan.scan_date) {
            throw 'Farmer map pin is missing own-farm scan details or exposes private fields.'
        }
    }
}
else {
    Write-Check 'farmer map returns all summaries and only own farm details' { throw 'Skipped because farmer login failed.' }
}

if ($script:FarmerToken) {
    Write-Check 'farmer submit without image returns 422' {
        $response = Invoke-MultipartUpload -Token $script:FarmerToken
        if ($response.Status -ne 422) {
            throw "Expected HTTP 422; received HTTP $($response.Status)."
        }
    }
}
else {
    Write-Check 'farmer submit without image returns 422' { throw 'Skipped because farmer login failed.' }
}

Write-Check 'missing token returns 401' {
    $response = Invoke-Api -Method Get -Path '/api/scans'
    if ($response.Status -ne 401) {
        throw "Expected HTTP 401; received HTTP $($response.Status)."
    }
}

Write-Check 'missing token alert list returns 401' {
    $response = Invoke-Api -Method Get -Path '/api/alerts'
    if ($response.Status -ne 401) {
        throw "Expected HTTP 401; received HTTP $($response.Status)."
    }
}

Write-Check 'missing token map returns 401' {
    $response = Invoke-Api -Method Get -Path '/api/map'
    if ($response.Status -ne 401) {
        throw "Expected HTTP 401; received HTTP $($response.Status)."
    }
}

Write-Check 'wrong password is rejected' {
    $response = Invoke-Api -Method Post -Path '/api/login' -Body @{
        email = 'api-farmer@test.local'
        password = 'not-the-password'
    }
    if ($response.Status -ne 401) {
        throw "Expected HTTP 401; received HTTP $($response.Status)."
    }
}

Write-Check 'farmer2 login' {
    $script:Farmer2Token = Get-LoginToken 'api-farmer2@test.local' 'password'
}

if ($script:Farmer2Token -and $script:FarmerScanId) {
    Write-Check 'farmer2 gets 403 for farmer1 scan' {
        $response = Invoke-Api -Method Get -Path "/api/scans/$script:FarmerScanId" -Headers (Get-AuthHeaders $script:Farmer2Token)
        if ($response.Status -ne 403) {
            throw "Expected HTTP 403; received HTTP $($response.Status)."
        }
    }
}
else {
    Write-Check 'farmer2 gets 403 for farmer1 scan' { throw 'Skipped because farmer2 login or farmer1 scan is unavailable.' }
}

if ($script:Farmer2Token -and $script:FarmerAlertId) {
    Write-Check 'farmer2 alert list excludes farmer1 alert' {
        $response = Invoke-Api -Method Get -Path '/api/alerts' -Headers (Get-AuthHeaders $script:Farmer2Token)
        if ($response.Status -ne 200 -or ($response.Data.data | Where-Object { $_.id -eq $script:FarmerAlertId })) {
            throw "Farmer2 alert list contains farmer1's alert or returned HTTP $($response.Status)."
        }
    }
    Write-Check 'farmer2 cannot mark farmer1 alert read' {
        $response = Invoke-Api -Method Post -Path "/api/alerts/$script:FarmerAlertId/read" -Headers (Get-AuthHeaders $script:Farmer2Token)
        if ($response.Status -ne 404) {
            throw "Expected HTTP 404; received HTTP $($response.Status)."
        }
    }
}
else {
    Write-Check 'farmer2 alert list excludes farmer1 alert' { throw 'Skipped because farmer2 login or farmer1 alert is unavailable.' }
    Write-Check 'farmer2 cannot mark farmer1 alert read' { throw 'Skipped because farmer2 login or farmer1 alert is unavailable.' }
}

Write-Check 'LGU login' {
    $script:LguToken = Get-LoginToken 'api-lgu@test.local' 'password'
}

if ($script:LguToken) {
    Write-Check 'LGU list includes farmer name and contact' {
        $response = Invoke-Api -Method Get -Path '/api/scans' -Headers (Get-AuthHeaders $script:LguToken)
        $farmerScan = $response.Data.data | Where-Object {
            $_.farmer.name -eq 'API Farmer One' -and
            $_.farmer.contact_number -eq '09170000001'
        } | Select-Object -First 1
        if ($response.Status -ne 200 -or $null -eq $farmerScan) {
            throw "LGU list did not include the seeded farmer name and contact (HTTP $($response.Status))."
        }
        $script:FarmerProfileId = [int]$farmerScan.farmer.id
    }

    Write-Check 'LGU alert list includes seeded outbreak alert' {
        $response = Invoke-Api -Method Get -Path '/api/alerts' -Headers (Get-AuthHeaders $script:LguToken)
        if ($response.Status -ne 200 -or -not ($response.Data.data | Where-Object { $_.id -eq $script:FarmerAlertId })) {
            throw "LGU alert list did not include the seeded alert (HTTP $($response.Status))."
        }
    }

    Write-Check 'LGU map includes province-wide outbreak details and scan pins' {
        $response = Invoke-Api -Method Get -Path '/api/map' -Headers (Get-AuthHeaders $script:LguToken)
        $farm = @($response.Data.farms | Where-Object { $_.name -eq 'API Farmer One' }) | Select-Object -First 1
        if ($response.Status -ne 200 -or @($response.Data.municipalities).Count -ne 12 -or
            -not ($response.Data.outbreaks | Where-Object { $_.disease -eq 'API Smoke Test Disease' }) -or
            $null -eq $farm -or $null -eq $farm.contact_number -or $null -eq $farm.scan.id -or
            $null -eq $farm.scan.disease -or $null -eq $farm.scan.scan_date) {
            throw "LGU map is missing province-wide details or active outbreak scan pins (HTTP $($response.Status))."
        }
    }
}
else {
    Write-Check 'LGU list includes farmer name and contact' { throw 'Skipped because LGU login failed.' }
    Write-Check 'LGU alert list includes seeded outbreak alert' { throw 'Skipped because LGU login failed.' }
    Write-Check 'LGU map includes province-wide outbreak details and scan pins' { throw 'Skipped because LGU login failed.' }
}

if ($script:LguToken -and $script:FarmerProfileId -and $script:VitReachable) {
    Write-Check 'LGU submits scan on behalf of farmer1' {
        $response = Invoke-MultipartUpload -Token $script:LguToken -IncludeImage -FarmerId $script:FarmerProfileId
        if ($response.Status -ne 201 -or $response.Data.status -ne 'completed') {
            throw "Expected a completed scan (HTTP 201); received HTTP $($response.Status)."
        }
        $script:LguScanId = [int]$response.Data.id
    }
}
else {
    Write-Check 'LGU submits scan on behalf of farmer1' { throw 'Skipped because LGU, farmer profile, or ViT service is unavailable.' }
}

if ($script:LguToken) {
    Write-Check 'LGU submit without farmer_id returns 422' {
        $response = Invoke-MultipartUpload -Token $script:LguToken -IncludeImage
        if ($response.Status -ne 422) {
            throw "Expected HTTP 422; received HTTP $($response.Status)."
        }
    }
}
else {
    Write-Check 'LGU submit without farmer_id returns 422' { throw 'Skipped because LGU login failed.' }
}

Write-Check 'admin login' {
    $script:AdminToken = Get-LoginToken 'api-admin@test.local' 'password'
}

if ($script:AdminToken) {
    Write-Check 'admin alert list includes seeded outbreak alert' {
        $response = Invoke-Api -Method Get -Path '/api/alerts' -Headers (Get-AuthHeaders $script:AdminToken)
        if ($response.Status -ne 200 -or -not ($response.Data.data | Where-Object { $_.id -eq $script:FarmerAlertId })) {
            throw "Admin alert list did not include the seeded alert (HTTP $($response.Status))."
        }
    }

    Write-Check 'admin map includes province-wide outbreak details and scan pins' {
        $response = Invoke-Api -Method Get -Path '/api/map' -Headers (Get-AuthHeaders $script:AdminToken)
        $farm = @($response.Data.farms | Where-Object { $_.name -eq 'API Farmer One' }) | Select-Object -First 1
        if ($response.Status -ne 200 -or @($response.Data.municipalities).Count -ne 12 -or
            -not ($response.Data.outbreaks | Where-Object { $_.disease -eq 'API Smoke Test Disease' }) -or
            $null -eq $farm -or $null -eq $farm.contact_number -or $null -eq $farm.scan.id -or
            $null -eq $farm.scan.disease -or $null -eq $farm.scan.scan_date) {
            throw "Admin map is missing province-wide details or active outbreak scan pins (HTTP $($response.Status))."
        }
    }
}
else {
    Write-Check 'admin alert list includes seeded outbreak alert' { throw 'Skipped because admin login failed.' }
    Write-Check 'admin map includes province-wide outbreak details and scan pins' { throw 'Skipped because admin login failed.' }
}

if ($script:FarmerToken) {
    Write-Check 'farmer logout succeeds' {
        $response = Invoke-Api -Method Post -Path '/api/logout' -Headers (Get-AuthHeaders $script:FarmerToken)
        if ($response.Status -ne 200) {
            throw "Logout returned HTTP $($response.Status)."
        }
    }

    Write-Check 'logged-out farmer token returns 401' {
        $response = Invoke-Api -Method Get -Path '/api/scans' -Headers (Get-AuthHeaders $script:FarmerToken)
        if ($response.Status -ne 401) {
            throw "Expected HTTP 401; received HTTP $($response.Status)."
        }
    }
}
else {
    Write-Check 'farmer logout succeeds' { throw 'Skipped because farmer login failed.' }
    Write-Check 'logged-out farmer token returns 401' { throw 'Skipped because farmer login failed.' }
}

Write-Host ''
Write-Host "Summary: $script:Passed passed, $script:Failed failed."
if ($script:Failed -gt 0) {
    exit 1
}
exit 0
