<?php

require_once __DIR__ . '/../config/Conexion.php';

class UsuarioModel
{
    // Guarda el objeto PDO que usará toda la clase.
    private $conexion;

    // Al crear el modelo, obtenemos la conexión lista para usar.
    public function __construct()
    {
        $this->conexion = Conexion::conectar();
    }

    // Busca un usuario por su correo y devuelve sus datos en un
    // arreglo (incluye el 'password_hash'). Devuelve 'false' si
    // el correo no existe, para que el controlador decida qué hacer.
    public function autenticar($correo)
    {
        // Consulta preparada con el parámetro nombrado ':correo'
    $sql = 'SELECT id, nombre, correo, password_hash, rol, estado
                FROM usuario
                WHERE correo = :correo
                LIMIT 1';
                
        // LIMIT 1: En cuanto encuentres el primer resultado que coincida con este correo, detén la búsqueda", ya que el correo es único.

                // prepare sirve para evitar la inyection sql y el usuario pueda entrar de forma segura
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':correo' => $correo]);

        // fetch() devuelve la fila encontrada o FALSE si no existe.
        return $consulta->fetch();
    }

    // Lista TODOS los usuarios del sistema para el módulo del admin
    // extendido (agregar doctores, recepcionistas y otros admins).
    public function listar()
    {
        $sql = 'SELECT id, nombre, correo, rol, estado
                FROM usuario
                ORDER BY rol, nombre';

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();

        return $consulta->fetchAll();
    }

    // Verifica si un correo YA existe (evita duplicados al crear).
    public function obtenerPorCorreo($correo)
    {
        $sql = 'SELECT id, correo
                FROM usuario
                WHERE correo = :correo
                LIMIT 1';

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':correo' => $correo]);

        return $consulta->fetch();
    }

    // Crea un usuario nuevo guardando la contraseña de forma
    // SEGURA con password_hash (criterio 3 de HU-01, nunca texto plano).
    public function crear($datos)
    {
        $sql = 'INSERT INTO usuario (nombre, correo, password_hash, rol, estado)
                VALUES (:nombre, :correo, :hash, :rol, "activo")';

        try {
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute([
                ':nombre' => $datos['nombre'],
                ':correo' => $datos['correo'],
                ':hash'   => password_hash($datos['clave'], PASSWORD_DEFAULT),
                ':rol'    => $datos['rol'],
            ]);
        } catch (PDOException $error) {
            return false;
        }

        return true;
    }

    // Activa o desactiva un usuario (despedir al personal sin borrar).
    public function cambiarEstado($id, $estado)
    {
        $sql = 'UPDATE usuario
                SET estado = :estado
                WHERE id = :id';

        try {
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute([':estado' => $estado, ':id' => $id]);
        } catch (PDOException $error) {
            return false;
        }

        return true;
    }
}
