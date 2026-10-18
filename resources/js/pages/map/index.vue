<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { show as scanShow } from '@/actions/App/Http/Controllers/ScanController';
import Map from 'ol/Map';
import Overlay from 'ol/Overlay';
import View from 'ol/View';
import Feature from 'ol/Feature';
import type { FeatureLike } from 'ol/Feature';
import Point from 'ol/geom/Point';
import TileLayer from 'ol/layer/Tile';
import VectorLayer from 'ol/layer/Vector';
import VectorSource from 'ol/source/Vector';
import GeoJSON from 'ol/format/GeoJSON';
import OSM from 'ol/source/OSM';
import { fromLonLat, transformExtent } from 'ol/proj';
import CircleStyle from 'ol/style/Circle';
import { Fill, Stroke, Style, Text } from 'ol/style';
import 'ol/ol.css';

type Severity = 'low' | 'medium' | 'high';
const FARM_PIN_ZOOM = 14;

interface OutbreakPin {
    id: number;
    disease: string;
    municipality: string;
    created_at: string | null;
    severity: Severity;
}

interface MunicipalityStatus {
    municipality: string;
    severity: Severity | null;
}

interface FarmPin {
    id: number;
    name?: string;
    contact_number?: string | null;
    barangay: string;
    municipality: string;
    lat: number;
    lng: number;
    scan: {
        id: number;
        disease: string | null;
        scan_date: string | null;
    } | null;
}

const props = defineProps<{
    outbreaks: OutbreakPin[];
    municipalityStatuses: MunicipalityStatus[];
    ownMunicipality: string | null;
    farms: FarmPin[];
}>();

const mapEl = ref<HTMLDivElement>();
const popupEl = ref<HTMLDivElement>();
const hoveredSlug = ref<string | null>(null);
const hoveredFarm = ref<string | null>(null);
const selectedFarm = ref<FarmPin | null>(null);
let resetView = () => {};
let zoomToMunicipality = (_slug: string) => {};
let closeFarmPopup = () => {};

const names: Record<string, string> = {
    abucay: 'Abucay',
    bagac: 'Bagac',
    balanga: 'Balanga City',
    dinalupihan: 'Dinalupihan',
    hermosa: 'Hermosa',
    limay: 'Limay',
    mariveles: 'Mariveles',
    morong: 'Morong',
    orani: 'Orani',
    orion: 'Orion',
    pilar: 'Pilar',
    samal: 'Samal',
};

const hoveredOutbreaks = computed(() =>
    props.outbreaks.filter((o) => o.municipality === hoveredSlug.value),
);

const canShowHoveredDetails = computed(
    () =>
        props.ownMunicipality === null ||
        hoveredSlug.value === props.ownMunicipality,
);

const municipalityStatusBySlug = computed(() => {
    const result: Record<string, Severity | null> = {};
    for (const status of props.municipalityStatuses) {
        result[status.municipality] = status.severity;
    }
    return result;
});

const statusColors: Record<Severity | 'none', string> = {
    none: 'rgba(34, 197, 94, 0.5)',
    low: 'rgba(250, 204, 21, 0.5)',
    medium: 'rgba(249, 115, 22, 0.55)',
    high: 'rgba(220, 38, 38, 0.6)',
};

const statusForMunicipality = (slug: string) =>
    municipalityStatusBySlug.value[slug] ?? null;

const statusColor = (severity: Severity | null) =>
    statusColors[severity ?? 'none'];

const severityLegend: { label: string; severity: Severity | null }[] = [
    { label: 'No outbreak', severity: null },
    { label: 'Low', severity: 'low' },
    { label: 'Medium', severity: 'medium' },
    { label: 'High', severity: 'high' },
];

onMounted(() => {
    const hoverStyle = new Style({
        stroke: new Stroke({ color: '#1d4ed8', width: 3 }),
        fill: new Fill({ color: 'rgba(59, 130, 246, 0.35)' }),
    });
    const hoverOutlineStyle = new Style({
        stroke: new Stroke({ color: '#1d4ed8', width: 3 }),
    });

    const severityStyle = (fill: string) =>
        new Style({
            stroke: new Stroke({ color: '#374151', width: 1.5 }),
            fill: new Fill({ color: fill }),
        });

    const severityStyles: Record<Severity, Style> = {
        low: severityStyle(statusColors.low),
        medium: severityStyle(statusColors.medium),
        high: severityStyle(statusColors.high),
    };
    const noOutbreakStyle = severityStyle(statusColors.none);

    const municipalityLabelStyles: Record<string, Style> = {};
    for (const [slug, name] of Object.entries(names)) {
        municipalityLabelStyles[slug] = new Style({
            text: new Text({
                text: name,
                font: '600 10px sans-serif',
                fill: new Fill({ color: '#374151' }),
                stroke: new Stroke({ color: '#ffffff', width: 3 }),
            }),
        });
    }

    let hovered: FeatureLike | null = null;

    const boundaryLayer = new VectorLayer({
        declutter: true,
        source: new VectorSource({
            url: '/geo/bataan-municipalities.geojson',
            format: new GeoJSON(),
        }),
        style: (feature) => {
            const slug = feature.get('municipality') as string;
            const severity = statusForMunicipality(slug);
            const statusStyle = severity
                ? severityStyles[severity]
                : noOutbreakStyle;
            const styles = [statusStyle];
            if (feature === hovered) styles.push(hoverOutlineStyle);
            const labelStyle = municipalityLabelStyles[slug];
            if (labelStyle) styles.push(labelStyle);
            return styles;
        },
    });

    const pinLayer = new VectorLayer({
        source: new VectorSource({
            features: props.farms.map(
                (farm) =>
                    new Feature({
                        geometry: new Point(fromLonLat([farm.lng, farm.lat])),
                        farmId: farm.id,
                        name: farm.name,
                    }),
            ),
        }),
        style: new Style({
            image: new CircleStyle({
                radius: 7,
                fill: new Fill({ color: '#16a34a' }),
                stroke: new Stroke({ color: '#ffffff', width: 2 }),
            }),
        }),
    });

    const map = new Map({
        target: mapEl.value,
        layers: [
            new TileLayer({ source: new OSM() }),
            new VectorLayer({
                source: new VectorSource({
                    url: '/geo/bataan-mask.geojson',
                    format: new GeoJSON(),
                }),
                style: new Style({
                    fill: new Fill({ color: '#f3f4f6' }),
                }),
                updateWhileInteracting: true,
                updateWhileAnimating: true,
            }),
            boundaryLayer,
            pinLayer,
        ],
        view: new View({
            center: fromLonLat([120.45, 14.65]),
            zoom: 10,
            minZoom: 9,
            maxZoom: 19,
            extent: transformExtent(
                [120.1, 14.25, 120.75, 15.05],
                'EPSG:4326',
                'EPSG:3857',
            ),
            constrainOnlyCenter: true,
            smoothExtentConstraint: false,
        }),
    });
    const farmOverlay = new Overlay({
        element: popupEl.value,
        positioning: 'bottom-center',
        offset: [0, -14],
        stopEvent: true,
    });
    map.addOverlay(farmOverlay);
    closeFarmPopup = () => {
        selectedFarm.value = null;
        farmOverlay.setPosition(undefined);
    };

    resetView = () => {
        const extent = boundaryLayer.getSource()?.getExtent();
        if (extent) {
            map.getView().fit(extent, {
                padding: [30, 30, 30, 30],
                duration: 600,
            });
        }
    };

    zoomToMunicipality = (slug) => {
        const feature = boundaryLayer
            .getSource()
            ?.getFeatures()
            .find((item) => item.get('municipality') === slug);
        const extent = feature?.getGeometry()?.getExtent();

        if (extent) {
            map.getView().fit(extent, {
                padding: [40, 40, 40, 40],
                duration: 600,
                maxZoom: 14,
            });
        }
    };

    boundaryLayer.getSource()?.once('featuresloadend', () => resetView());

    map.on('pointermove', (event) => {
        if (event.dragging) return;

        const pin = map.forEachFeatureAtPixel(event.pixel, (f) => f, {
            layerFilter: (layer) => layer === pinLayer,
        });
        const farmName = pin?.get('name');
        hoveredFarm.value =
            typeof farmName === 'string'
                ? farmName
                : pin && props.ownMunicipality !== null
                  ? 'Your farm'
                  : null;

        const feature = map.forEachFeatureAtPixel(event.pixel, (f) => f, {
            layerFilter: (layer) => layer === boundaryLayer,
        });

        if (feature !== hovered) {
            hovered = feature ?? null;
            hoveredSlug.value = hovered?.get('municipality') ?? null;
            boundaryLayer.changed();
            map.getTargetElement().style.cursor =
                pin || hovered ? 'pointer' : '';
        } else {
            map.getTargetElement().style.cursor = pin ? 'pointer' : '';
        }
    });

    map.on('click', (event) => {
        const pin = map.forEachFeatureAtPixel(event.pixel, (f) => f, {
            layerFilter: (layer) => layer === pinLayer,
        });
        if (pin) {
            const farmId = pin.get('farmId');
            const farm = props.farms.find((item) => item.id === farmId);
            const geometry = pin.getGeometry();
            if (farm && geometry instanceof Point) {
                const coordinate = geometry.getCoordinates();
                selectedFarm.value = farm;
                farmOverlay.setPosition(coordinate);

                const view = map.getView();
                const currentZoom = view.getZoom() ?? 0;
                view.animate({
                    center: coordinate,
                    ...(currentZoom < FARM_PIN_ZOOM
                        ? { zoom: FARM_PIN_ZOOM }
                        : {}),
                    duration: 400,
                });
            }
            return;
        }

        closeFarmPopup();

        const feature = map.forEachFeatureAtPixel(event.pixel, (f) => f, {
            layerFilter: (layer) => layer === boundaryLayer,
        });

        const slug = feature?.get('municipality');
        if (typeof slug === 'string') {
            zoomToMunicipality(slug);
        }
    });
});
</script>

<template>
    <Head title="Outbreak Map" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">
            <div class="relative">
                <div ref="mapEl" class="h-128 w-full rounded-lg shadow"></div>

                <div
                    ref="popupEl"
                    v-show="selectedFarm"
                    class="max-h-[calc(100vh-2rem)] w-64 max-w-[calc(100vw-2rem)] overflow-y-auto rounded-lg border border-gray-200 bg-white p-4 text-sm shadow-xl"
                >
                    <button
                        type="button"
                        class="absolute top-2 right-2 rounded p-1 text-lg leading-none text-gray-500 hover:bg-gray-100 hover:text-gray-800"
                        aria-label="Close farm details"
                        @click.stop="closeFarmPopup()"
                    >
                        &times;
                    </button>
                    <h3 class="pr-6 font-semibold text-gray-900">
                        {{
                            selectedFarm?.name ??
                            (ownMunicipality === null ? 'Farm' : 'Your farm')
                        }}
                    </h3>
                    <dl
                        v-if="selectedFarm"
                        class="mt-2 space-y-1 text-gray-600"
                    >
                        <template v-if="selectedFarm.name !== undefined">
                            <div class="flex justify-between gap-2">
                                <dt>Contact</dt>
                                <dd class="text-right">
                                    {{
                                        selectedFarm.contact_number ??
                                        'Not provided'
                                    }}
                                </dd>
                            </div>
                        </template>
                        <div class="flex justify-between gap-2">
                            <dt>Barangay</dt>
                            <dd class="text-right">
                                {{ selectedFarm.barangay }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt>Municipality</dt>
                            <dd class="text-right">
                                {{ names[selectedFarm.municipality] }}
                            </dd>
                        </div>
                        <div class="border-t border-gray-100 pt-2">
                            <p class="font-medium text-gray-800">Latest scan</p>
                            <template v-if="selectedFarm.scan">
                                <p>
                                    {{
                                        selectedFarm.scan.disease ??
                                        'No disease detected'
                                    }}
                                </p>
                                <p>
                                    {{
                                        selectedFarm.scan.scan_date ??
                                        'Date unavailable'
                                    }}
                                </p>
                                <Link
                                    :href="scanShow(selectedFarm.scan.id).url"
                                    class="mt-1 inline-block font-medium text-emerald-700 underline hover:text-emerald-800"
                                >
                                    View scan details
                                </Link>
                            </template>
                            <p v-else class="text-gray-500">
                                No scans recorded yet.
                            </p>
                        </div>
                    </dl>
                </div>

                <div
                    v-if="hoveredSlug"
                    class="pointer-events-none absolute top-14 left-4 z-10 w-56 rounded-lg bg-white p-4 shadow"
                >
                    <p class="font-semibold text-gray-800">
                        {{ names[hoveredSlug] }}
                    </p>
                    <p
                        v-if="canShowHoveredDetails && hoveredOutbreaks.length"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ hoveredOutbreaks.length }} active outbreak(s)
                    </p>
                    <p
                        v-else-if="canShowHoveredDetails"
                        class="mt-1 text-sm text-gray-500"
                    >
                        No active outbreaks
                    </p>
                    <ul
                        v-if="canShowHoveredDetails"
                        class="mt-1 text-sm text-gray-600"
                    >
                        <li v-for="o in hoveredOutbreaks" :key="o.id">
                            {{ o.disease }}
                        </li>
                    </ul>
                    <p
                        v-if="canShowHoveredDetails && hoveredFarm"
                        class="mt-2 text-sm font-medium text-gray-800"
                    >
                        Farm: {{ hoveredFarm }}
                    </p>
                </div>

                <button
                    type="button"
                    class="absolute bottom-4 left-4 rounded-md bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow hover:bg-gray-50"
                    @click="resetView()"
                >
                    Reset view
                </button>

                <aside
                    class="absolute top-4 right-4 max-h-[60%] w-64 max-w-[calc(100%-1rem)] overflow-y-auto rounded-lg bg-white/95 p-3 shadow"
                    aria-label="Map legend and municipality status"
                >
                    <ul
                        class="grid grid-cols-2 gap-x-2 gap-y-1 text-xs text-gray-700"
                        aria-label="Outbreak severity colors"
                    >
                        <li
                            v-for="item in severityLegend"
                            :key="item.label"
                            class="flex items-center gap-1.5"
                        >
                            <span
                                class="h-2.5 w-2.5 shrink-0 rounded-full"
                                :style="{
                                    backgroundColor: statusColor(item.severity),
                                }"
                            ></span>
                            {{ item.label }}
                        </li>
                    </ul>
                    <h4
                        class="mt-3 border-t border-gray-200 pt-2 text-xs font-semibold text-gray-800"
                    >
                        Municipalities
                    </h4>
                    <ul class="mt-1 space-y-0.5">
                        <li v-for="(severity, slug) in names" :key="slug">
                            <button
                                type="button"
                                class="flex w-full items-center gap-2 rounded px-1.5 py-1 text-left text-xs text-gray-700 hover:bg-gray-100 focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-none"
                                @click="zoomToMunicipality(slug)"
                            >
                                <span
                                    class="h-2.5 w-2.5 shrink-0 rounded-full"
                                    :style="{
                                        backgroundColor: statusColor(
                                            statusForMunicipality(slug),
                                        ),
                                    }"
                                ></span>
                                {{ severity }}
                            </button>
                        </li>
                    </ul>
                </aside>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
