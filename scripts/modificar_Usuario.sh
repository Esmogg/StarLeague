#!/bin/bash

LOG="/home/uits/gestion_Usuarios/gestion_usuarios.log"

ACTOR=$USER
IP=$(who am i | awk '{print $5}')

echo "Ingresando al Script de Modificación de Usuarios"

read -p "Ingrese el Usuario a modificar: " usuario

if [ "$usuario" = "root" ]; then
    echo "No se puede modificar el usuario root"
    echo "$(date) - Actor: $ACTOR - IP: $IP - Intento de modificar usuario root" | sudo tee -a "$LOG"

    ./menu.sh
    exit 1
fi

if id "$usuario" &>/dev/null; then

    echo "1 - Cambiar nombre de usuario"
    echo "2 - Cambiar de grupo"
    echo "3 - Salir"

    read -p "Seleccione una opción: " opcion

    case $opcion in

        1)
            read -p "Nuevo nombre de usuario: " new_Name

            if sudo usermod -l "$new_Name" "$usuario"; then
                echo "Usuario renombrado correctamente"
                echo "$(date) - Actor: $ACTOR - IP: $IP - Usuario $usuario renombrado a $new_Name" | sudo tee -a "$LOG"

                ./menu.sh
                exit 0
            else
                echo "Error al cambiar de nombre"
                echo "$(date) - Actor: $ACTOR - IP: $IP - Error al cambiar nombre de $usuario a $new_Name" | sudo tee -a "$LOG"

                ./menu.sh
                exit 1
            fi
            ;;

        2)
            echo "Grupos disponibles (arquitectura del sistema):"
            echo "1 - admin (root / superusuario del sistema)"
            echo "2 - dba (administrador de base de datos)"
            echo "3 - backend_api (cuenta de servicio de la aplicación)"
            echo "4 - auditoria (usuario de monitoreo, solo lectura)"

            read -p "Seleccione grupo: " grupo

            case $grupo in
                1) grupo="admin" ;;
                2) grupo="dba" ;;
                3) grupo="backend_api" ;;
                4) grupo="auditoria" ;;
                *)
                    echo "Opción inválida"
                    echo "$(date) - Actor: $ACTOR - IP: $IP - Opción de grupo inválida" | sudo tee -a "$LOG"

                    ./menu.sh
                    exit 1
                    ;;
            esac

            if ! getent group "$grupo" &>/dev/null; then
                echo "El grupo $grupo no existe. Creándolo..."

                if sudo groupadd "$grupo"; then
                    echo "Grupo $grupo creado correctamente"
                    echo "$(date) - Actor: $ACTOR - IP: $IP - Grupo $grupo creado" | sudo tee -a "$LOG"
                else
                    echo "Error al crear el grupo $grupo"
                    echo "$(date) - Actor: $ACTOR - IP: $IP - Error al crear grupo $grupo" | sudo tee -a "$LOG"

                    ./menu.sh
                    exit 1
                fi
            fi

            if sudo usermod -aG "$grupo" "$usuario"; then
                echo "Usuario cambiado al grupo con éxito"
                echo "$(date) - Actor: $ACTOR - IP: $IP - Acción: Cambio de grupo - Usuario $usuario añadido al grupo $grupo" | sudo tee -a "$LOG"

                ./menu.sh
                exit 0
            else
                echo "Error al añadir grupo"
                echo "$(date) - Actor: $ACTOR - IP: $IP - Error al añadir usuario $usuario al grupo $grupo" | sudo tee -a "$LOG"

                ./menu.sh
                exit 1
            fi
            ;;

        3)
            echo "Saliendo"
            exit 0
            ;;

        *)
            echo "Opción inválida"
            echo "$(date) - Actor: $ACTOR - IP: $IP - Opción inválida seleccionada" | sudo tee -a "$LOG"

            ./menu.sh
            exit 1
            ;;

    esac

else
    echo "El usuario no existe"
    echo "$(date) - Actor: $ACTOR - IP: $IP - Intento modificar usuario inexistente $usuario" | sudo tee -a "$LOG"

    ./menu.sh
    exit 1
fi
