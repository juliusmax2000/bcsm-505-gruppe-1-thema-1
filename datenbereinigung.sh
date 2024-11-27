#!/bin/bash
Speicher="/home/user/caddy_test/public/uploads"
find "$Speicher" -iname "*.pdf" -type f -mtime +30 -delete