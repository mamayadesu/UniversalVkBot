#!/bin/bash
xrefcore-compiler -b
unlink ~/uvb/UniversalVkBot.phar
cp UniversalVkBot.phar ~/uvb/UniversalVkBot.phar
for (( ; ; ))
do
    php7.4 ~/uvb/UniversalVkBot.phar
    EXITCODE=$?
    
    if [ $EXITCODE -eq 0 ]
    then
        break
    fi
    
    if [ $EXITCODE -eq 255 ] 
    then
        echo "UniversalVkBot was crashed. Check crash dump above. Do you want to restart UniversalVkBot [y/n]?: "
        read restart
        if [ "$restart" != "y" ]
        then
            break;
        fi
    fi
done
