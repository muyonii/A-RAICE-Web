const dateFormatter = new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
});

const timeFormatter = new Intl.DateTimeFormat('en-US', {
    hour: 'numeric',
    minute: '2-digit',
});

export function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    const localDateTime = value.match(
        /^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2}):(\d{2})(?:\.\d+)?)?$/,
    );
    const date = localDateTime
        ? new Date(
              Number(localDateTime[1]),
              Number(localDateTime[2]) - 1,
              Number(localDateTime[3]),
              Number(localDateTime[4] ?? 0),
              Number(localDateTime[5] ?? 0),
              Number(localDateTime[6] ?? 0),
          )
        : new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    const formattedDate = dateFormatter.format(date);
    return localDateTime?.[4]
        ? `${formattedDate}, ${timeFormatter.format(date)}`
        : formattedDate;
}
