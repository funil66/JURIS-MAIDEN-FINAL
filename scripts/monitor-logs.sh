#!/usr/bin/env bash
# Simple log monitor: writes any new ERROR lines to storage/logs/monitor-errors.log
LOGFILE="src/storage/logs/laravel.log"
OUTFILE="src/storage/logs/monitor-errors.log"

touch "$OUTFILE"
# Follow and filter for ERROR, writing timestamped lines
stdbuf -oL tail -n 0 -f "$LOGFILE" | stdbuf -oL grep --line-buffered -i "error" | while read -r line; do
  echo "$(date '+%F %T') - $line" >> "$OUTFILE"
done
