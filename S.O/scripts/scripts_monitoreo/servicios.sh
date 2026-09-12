#!/bin/bash

estado() {
    read -p "Ingrese el nombre del servicio: " servicio

    sudo systemctl status "$servicio"
}

iniciar() {
    read -p "Ingrese el nombre del servicio: " servicio

    sudo systemctl start "$servicio"

    if [ $? -eq 0 ]; then
        echo "Servicio iniciado correctamente."
    else
        echo "Error al iniciar el servicio."
    fi
}

detener() {
    read -p "Ingrese el nombre del servicio: " servicio

    sudo systemctl stop "$servicio"

    if [ $? -eq 0 ]; then
        echo "Servicio detenido correctamente."
    else
        echo "Error al detener el servicio."
    fi
}

reiniciar() {
    read -p "Ingrese el nombre del servicio: " servicio

    sudo systemctl restart "$servicio"

    if [ $? -eq 0 ]; then
        echo "Servicio reiniciado correctamente."
    else
        echo "Error al reiniciar el servicio."
    fi
}

habilitar() {
    read -p "Ingrese el nombre del servicio: " servicio

    sudo systemctl enable "$servicio"

    if [ $? -eq 0 ]; then
        echo "Servicio habilitado."
    else
        echo "Error al habilitar el servicio."
    fi
}

deshabilitar() {
    read -p "Ingrese el nombre del servicio: " servicio

    sudo systemctl disable "$servicio"

    if [ $? -eq 0 ]; then
        echo "El servicio ya no se iniciará automáticamente."
    else
        echo "Error al deshabilitar el servicio."
    fi
}

listar() {
    echo "==== Servicios Activos ===="

    systemctl list-units --type=service --state=running
}

logs() {
    read -p "Ingrese el nombre del servicio: " servicio
    
    echo "LOGS DE $servicio"

    sudo journalctl -u "$servicio"
}



while true
do
    clear

    echo -e "\n========== GESTIÓN DE SERVICIOS =========="
    echo "1) Ver estado de un servicio"
    echo "2) Iniciar un servicio"
    echo "3) Detener un servicio"
    echo "4) Reiniciar un servicio"
    echo "5) Habilitar servicio al iniciar Linux"
    echo "6) Deshabilitar servicio al iniciar Linux"
    echo "7) Listar servicios activos"
    echo "8) Ver logs de un servicio"
    echo "9) Salir"
    echo "======================================"

    read -p "Seleccione una opción: " opcion

    case $opcion in
        1)
            estado
            ;;
        2)
            iniciar
            ;;
        3)
            detener
            ;;
        4)
            reiniciar
            ;;
        5)
            habilitar
            ;;
        6)
            deshabilitar
            ;;
        7)
            listar
            ;;
        8)
            logs
            ;;
        9)
            echo "Saliendo..."
            exit 0
            ;;
        *)
            echo "Opción inválida."
            ;;
    esac

    echo
    read -p "Presione Enter para continuar..."
done
