<?php

require_once __DIR__ . '/../Config/conexion.php';

class Actividad{

    private $conexion;

    public function __construct() 
    {
        $conexionDB = new Conexion();
        $this->conexion = $conexionDB->conectar();
        }

public function obtenerActividades() {
      $sql = "SELECT * FROM actividades ORDER BY fechaEvento ASC";
    $consulta = $this->conexion->prepare($sql);
    $consulta->execute();
    return $consulta->fetchAll();
      }

      public function guardar($datos){
        $sql = "INSERT INTO actividades (nombreActividad, fechaEvento, municipio ) 
        VALUES (:nombre, :fechaEvento, :municipio)";
        $consulta = $this->conexion->prepare($sql);
        return $consulta->execute([
            ':nombre' => $datos['nombreActividad'],
            ':fechaEvento' => $datos['fechaEvento'],
            ':municipio' => $datos['municipio']
        ]);
      }




      public function eliminar($idActividad){
        $sql = "DELETE FROM actividades WHERE idActividad = :idActividad";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':idActividad' => $idActividad]);
        return $consulta->rowCount() > 0; // Retorna true si se elimino alguna fila
        }


        public function editar($datos){
          $sql = "UPDATE actividades SET nombreActividad = :nombre, fechaEvento = :fechaEvento, municipio = :municipio, totalRecaudado = :totalRecaudado WHERE idActividad = :idActividad";
        $consulta = $this->conexion->prepare($sql);
        return $consulta->execute([
            ':nombre' => $datos['nombreActividad'],
            ':fechaEvento' => $datos['fechaEvento'],
            ':municipio' => $datos['municipio'],
            ':totalRecaudado' => $datos['totalRecaudado'],
            ':idActividad' => $datos['idActividad']
        ]);
        if ($consulta->rowCount() > 0) {
            return true; // Retorna true si se actualizó alguna fila
          }



          $verificar = $this->conexion->prepare("SELECT 1 FROM actividades WHERE idActividad = :idActividad");
          $verificar->execute([':idActividad' => $datos['idActividad']]);

          return(bool)$verificar->fetchColumn(); // Retorna true si existe el registro, false si no existe
        }
}