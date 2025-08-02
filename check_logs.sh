#!/bin/bash

# Function to send JSON content to the printer
send_to_printer() {
    local json_content="$1"
    echo "$json_content" | sh -c "cat > /tmp/output.json" && cat /tmp/output.json > /dev/usb/lp0
}

# Function to process command execution log JSON
process_command_log() {
    local logfile="$1"
    local logtype="$2"  # 'command' or 'receipt'

    # Check if log file exists and is not empty
    if [ ! -f "$logfile" ] || [ ! -s "$logfile" ]; then
        echo "Log file $logfile not found or empty."
        return
    fi

    # Get today's date in YYYY-MM-DD
    local TODAY_DATE=$(date +%Y-%m-%d)

    # Read all entries into an array
    mapfile -t entries < <(jq -c '.[]' "$logfile")
    local total_entries=${#entries[@]}

    # Process all entries except the last
    for (( i=0; i<total_entries-1; i++ )); do
        local entry="${entries[i]}"
        # Extract timestamp string
        local timestamp
        timestamp=$(echo "$entry" | jq -r '.timestamp')

        # Convert timestamp to epoch seconds
        local entry_time
        entry_time=$(date -d "$timestamp" +%s 2>/dev/null)
        if [ $? -ne 0 ] || [ -z "$entry_time" ]; then
            continue
        fi

        # Extract the date part of the entry's timestamp
        local entry_date
        entry_date=$(date -d "$timestamp" +%Y-%m-%d)

        # Check if the entry is from today
        if [ "$entry_date" != "$TODAY_DATE" ]; then
            continue
        fi

        # Generate JSON based on log type
        if [ "$logtype" = "command" ]; then
            id=$(echo "$entry" | jq -r '.id')
            message=$(echo "$entry" | jq -r '.details.message')
            capcode=$(echo "$entry" | jq -r '.details.capcode')
            frequency=$(echo "$entry" | jq -r '.details.frequency')
            baud=$(echo "$entry" | jq -r '.details.baud')
            inversion=$(echo "$entry" | jq -r '.details.inversion')
            description=$(echo "$entry" | jq -r '.details.description')

            json_output=$(jq -n --arg id "$id" \
                                --arg timestamp "$timestamp" \
                                --arg message "$message" \
                                --arg capcode "$capcode" \
                                --arg frequency "$frequency" \
                                --arg baud "$baud" \
                                --arg inversion "$inversion" \
                                --arg description "$description" \
                        '{
                            id: ($id | tonumber),
                            timestamp: $timestamp,
                            details: {
                                message: $message,
                                capcode: $capcode,
                                frequency: $frequency,
                                baud: $baud,
                                inversion: $inversion,
                                description: $description
                            }
                        }')
            send_to_printer "$json_output"

        elif [ "$logtype" = "receipt" ]; then
            action=$(echo "$entry" | jq -r '.action')
            json_output=$(jq -n --arg timestamp "$timestamp" --arg action "$action" '{
                timestamp: $timestamp,
                action: $action
            }')
            send_to_printer "$json_output"
        fi
    done

    # Process the last entry separately
    if [ "$total_entries" -gt 0 ]; then
        local last_entry="${entries[$((total_entries-1))]}"
        local timestamp
        timestamp=$(echo "$last_entry" | jq -r '.timestamp')

        local entry_time
        entry_time=$(date -d "$timestamp" +%s 2>/dev/null)
        if [ $? -eq 0 ] && [ -n "$entry_time" ]; then
            local entry_date
            entry_date=$(date -d "$timestamp" +%Y-%m-%d)
            if [ "$entry_date" = "$TODAY_DATE" ]; then
                if [ "$logtype" = "command" ]; then
                    id=$(echo "$last_entry" | jq -r '.id')
                    message=$(echo "$last_entry" | jq -r '.details.message')
                    capcode=$(echo "$last_entry" | jq -r '.details.capcode')
                    frequency=$(echo "$last_entry" | jq -r '.details.frequency')
                    baud=$(echo "$last_entry" | jq -r '.details.baud')
                    inversion=$(echo "$last_entry" | jq -r '.details.inversion')
                    description=$(echo "$last_entry" | jq -r '.details.description')

                    json_output=$(jq -n --arg id "$id" \
                                        --arg timestamp "$timestamp" \
                                        --arg message "$message" \
                                        --arg capcode "$capcode" \
                                        --arg frequency "$frequency" \
                                        --arg baud "$baud" \
                                        --arg inversion "$inversion" \
                                        --arg description "$description" \
                                '{
                                    id: ($id | tonumber),
                                    timestamp: $timestamp,
                                    details: {
                                        message: $message,
                                        capcode: $capcode,
                                        frequency: $frequency,
                                        baud: $baud,
                                        inversion: $inversion,
                                        description: $description
                                    }
                                }')
                    send_to_printer "$json_output"

                elif [ "$logtype" = "receipt" ]; then
                    action=$(echo "$last_entry" | jq -r '.action')
                    json_output=$(jq -n --arg timestamp "$timestamp" --arg action "$action" '{
                        timestamp: $timestamp,
                        action: $action
                    }')
                    send_to_printer "$json_output"
                fi
            fi
        fi
    fi
}

# Process the first JSON log (command_execution_log.json)
process_command_log "/var/www/dashboard/command_execution_log.json" "command"

# Process the second JSON log (print_receipt_log.json)
process_command_log "/var/www/dashboard/print_receipt_log.json" "receipt"

# Add 3 empty JSON objects (or as needed)
for i in {1..4}; do
    echo "" | sh -c "cat > /tmp/output.json" && cat /tmp/output.json > /dev/usb/lp0
done

# Send command to pulse the printer
sudo echo -ne '\x1B\x64\x01' | sudo tee /dev/usb/lp0 > /dev/null
