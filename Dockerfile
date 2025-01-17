# Start from the base caddy:2-alpine image
FROM caddy:2-alpine

# Ensure apk is available and install tzdata
RUN apk update && apk add --no-cache tzdata

# Set your desired timezone (change this to your preferred timezone)
RUN cp /usr/share/zoneinfo/Europe/Berlin /etc/localtime && echo "Europe/Berlin" > /etc/timezone

# Clean up tzdata package to reduce image size
RUN apk del tzdata

# Expose necessary ports
EXPOSE 80 443

# Run Caddy
CMD ["caddy", "run", "--config", "/etc/caddy/Caddyfile", "--adapter", "caddyfile"]