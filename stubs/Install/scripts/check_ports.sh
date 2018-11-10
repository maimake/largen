#!/bin/sh

#3000 browsersync proxy
#3001 browsersync settings
#8080 webpack-dev-server

for port in 3000 3001 8080
do
    lsof -iTCP:$port -sTCP:LISTEN -n -P && echo "\033[31m The port number :$port is busy, please close other program first \033[0m" && exit 1
done

exit 0
