<?php
//Archivos de configuracion y funciones necesarias
include("../../config/consultas_bd/conexion_bd.php");
include("../../config/consultas_bd/consultas_preguntas_respuesta.php");
include("../../config/consultas_bd/consultas_usuario.php");
include("../../config/consultas_bd/consultas_producto.php");
include("../../config/funciones/funciones_verificaciones.php");
include("../../config/consultas_bd/consultas_notificacion.php");


// Campos esperados en la solicitud POST
$campo_esperados = array("id_usuario", "tipo_usuario", "id_pregunta");
$respuesta = [];

try {
    //Verifica si la solicitud es POST y no esta vacia
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {

        //Verifica la entrada de datos esperados
        $mensaje = verificarEntradaDatosArray($campo_esperados, $_POST);
        if (!empty($mensaje)) {
            throw new Exception($mensaje);
        }

        //Se obtiene los datos de la solicitud POST
        $id_pregunta = $_POST['id_pregunta'];
        $id_usuario = $_POST['id_usuario'];
        $tipo_usuario = $_POST['tipo_usuario'];


        // Establecer conexión con la base de datos
        $conexion = obtenerConexionBD();

        //Verifica si el usuario es valido
        if (!verificarSiEsUsuarioValido($conexion, $id_usuario, $tipo_usuario)) {
            throw new Exception("No se puede eliminar una pregunta debido a que su cuenta no esta activa o esta baneado");
        }


        //Valida que los campos ocultos solo contengan numeros
        if (!is_numeric($id_pregunta)) {
            throw new Exception("Los campos ocultos solo deben contener numeros");
        }

        //Verifica si el usuario hizo la pregunta
        if (!elUsuarioHizoLaPregunta($conexion, $id_pregunta, $id_usuario)) {
            throw new Exception("Un usuario no puede eliminar las preguntas que no halla hecho");
        }


        //Verifica si la pregunta del usuario sigue disponible
        if (!laPreguntaSigueDisponible($conexion, $id_pregunta)) {
            throw new Exception("Esta pregunta fue eliminada previamente. Por favor actualiza la pantalla para ver los cambios");
        }


        //Verifica si la pregunta del usuario fue respondida
        if (laPreguntaFueRespondida($conexion, $id_pregunta)) {
            throw new Exception("No se pueden eliminar preguntas que fueron respondidas");
        }


        //Se obtiene informacion sobre el usuario que hizo la pregunta
        $preguntas = obtenerIdUsuarioPreguntaProducto($conexion, $id_pregunta);
        $id_usuario_notificar = $preguntas['id_usuario'];

        //Se elimina la pregunta
        bajaPreguntaProducto($conexion, $id_pregunta);

        //Se elimina la notificacion de la pregunta al emprendedor que la recibio
        bajaNotificacionPreguntaProducto($conexion, $id_usuario_notificar, $id_usuario, $id_pregunta);
        $respuesta['estado'] = 'success';
        $respuesta['mensaje'] = 'Se elimino la pregunta correctamente';

        http_response_code(200);
    } else {
        http_response_code(405);
        throw new Exception("Metodo no permitido o datos no recibidos");
    }
} catch (Exception $e) {
    //Capturar cualquier excepción y guardar el mensaje de error en la respuesta
    http_response_code(400);
    $respuesta['mensaje'] = $e->getMessage();
}
//Devolver la respuesta en formato JSON
echo json_encode($respuesta);