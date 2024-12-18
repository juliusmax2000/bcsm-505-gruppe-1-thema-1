#!/bin/bash
Speicher="/home/user/caddy_test/public/uploads"
find "$Speicher" -type d -mtime +30 -exec rm -rf