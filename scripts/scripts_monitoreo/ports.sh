#!/bin/bash

while true; do
    clear
    echo "----------------- Administracion de Puertos -----------------"
    echo "1) Abrir Puerto (TCP)"
    echo "2) Cerrar Puerto (TCP)"
    echo "0) Salir"
    echo -e "---------------------------------------------------------\n"
    
    read -p "Elija una opcion: " opc

    case $opc in
        1)
            read -p "Ingrese el numero del puerto a abrir (ej. 80): " ap
            firewall-cmd --add-port=$ap/tcp
            clear
            echo "El puerto $ap/tcp ha sido abierto"
            read -p "Presione ENTER para continuar... " boton
            ;;
        2)
            read -p "Ingrese el numero del puerto a cerrar (ej. 80): " cp
            firewall-cmd --remove-port=$cp/tcp
            clear
            echo "El puerto $cp/tcp ha sido cerrado"
            read -p "Presione ENTER para continuar... " boton
            ;;
        0)
            echo "Saliendo..."
            break
            ;;
        *)
            echo "Opción inválida"
            sleep 2
            ;;
    esac
done
