#!/bin/bash

# Path to your JSON logfile
LOGFILE="/var/www/dashboard/command_execution_log.json"

# Check if log file exists and is not empty
if [ ! -f "$LOGFILE" ] || [ ! -s "$LOGFILE" ]; then
    echo "Log file not found or empty."
    exit 0
fi

# Get today's date in YYYY-MM-DD
TODAY_DATE=$(date +%Y-%m-%d)

# Read each JSON object in the array
jq -c '.[]' "$LOGFILE" | while read -r entry; do
    # Extract timestamp string
    timestamp=$(echo "$entry" | jq -r '.timestamp')

    # Convert timestamp to epoch seconds
    entry_time=$(date -d "$timestamp" +%s 2>/dev/null)
    if [ $? -ne 0 ] || [ -z "$entry_time" ]; then
        continue
    fi

    # Extract the date part of the entry's timestamp
    entry_date=$(date -d "$timestamp" +%Y-%m-%d)

    # Check if the entry is from today
    if [ "$entry_date" != "$TODAY_DATE" ]; then
        continue
    fi

    # Prepare the output JSON
    id=$(echo "$entry" | jq -r '.id')
    message=$(echo "$entry" | jq -r '.details.message')
    capcode=$(echo "$entry" | jq -r '.details.capcode')
    frequency=$(echo "$entry" | jq -r '.details.frequency')
    baud=$(echo "$entry" | jq -r '.details.baud')
    inversion=$(echo "$entry" | jq -r '.details.inversion')

    # Send the data
    jq -n --arg id "$id" \
          --arg timestamp "$timestamp" \
          --arg message "$message" \
          --arg capcode "$capcode" \
          --arg frequency "$frequency" \
          --arg baud "$baud" \
          --arg inversion "$inversion" \
    '{
        id: ($id | tonumber),
        timestamp: $timestamp,
        details: {
            message: $message,
            capcode: $capcode,
            frequency: $frequency,
            baud: $baud,
            inversion: $inversion
        }
    }' | sh -c "cat > /tmp/output.json" && cat /tmp/output.json > /dev/usb/lp0

    # Add 8 empty lines (empty JSON objects)
    for i in {1..8}; do
        echo "{}" | sh -c "cat > /tmp/empty.json" && cat /tmp/empty.json > /dev/usb/lp0
    done
done
