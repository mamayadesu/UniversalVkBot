for (( ; ; ))
do
    php UniversalVkBot.phar
    if [ $? -eq 0 ]
    then
        break
    fi
    
    if [ $? -eq 255 ] 
    then
        echo "UniversalVkBot was crashed. Check crash dump above. Do you want to restart UniversalVkBot [y/n]?: "
        read restart
        if [ $restart != "y" ]
        then
            break;
        fi
    fi
done