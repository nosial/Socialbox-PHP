#!/bin/bash

# Banner with cool ASCII art
echo "███████╗ ██████╗  ██████╗██╗ █████╗ ██╗     ██████╗  ██████╗ ██╗  ██╗"
echo "██╔════╝██╔═══██╗██╔════╝██║██╔══██╗██║     ██╔══██╗██╔═══██╗╚██╗██╔╝"
echo "███████╗██║   ██║██║     ██║███████║██║     ██████╔╝██║   ██║ ╚███╔╝ "
echo "╚════██║██║   ██║██║     ██║██╔══██║██║     ██╔══██╗██║   ██║ ██╔██╗ "
echo "███████║╚██████╔╝╚██████╗██║██║  ██║███████╗██████╔╝╚██████╔╝██╔╝ ██╗"
echo "╚══════╝ ╚═════╝  ╚═════╝╚═╝╚═╝  ╚═╝╚══════╝╚═════╝  ╚═════╝ ╚═╝  ╚═╝"

# Check if the environment variable SB_MODE is set to "automated", if not exit.
if [ "$SB_MODE" != "automated" ]; then
    echo "SB_MODE is not set to 'automated', exiting..."
    exit 1
fi

# Initialize Socialbox
echo "Initializing Socialbox..."
/usr/bin/socialbox init --log-level=${LOG_LEVEL-INFO}

# Run supervisord, final command
/usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf