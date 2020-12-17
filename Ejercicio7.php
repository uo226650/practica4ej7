<!DOCTYPE html>
<html lang="es">
<head>
    <title>BaseDatos :: Tareas de asignaturas</title>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="Rocío Cenador Martínez" /> 
    <link href="Ejercicio7.css" rel="stylesheet" />
</head>   
<body>
    <header>
        <h1>Gestionar tareas pendientes</h1>
    </header>    
   
        <form action="#" method="POST">
        <h2>Menú:</h2>
            <label>Administración:</label>
            <input type="submit" value="Crear Base de Datos" name="creaBaseDatos" />
            <input type="submit" value="Crear Tablas" name="creaTablas" />
            <label>Visualización:</label>
            <input type="submit" value="Ver Lista" name="verLista" />
            <input type="submit" value="Ver Asignaturas" name="verAsignaturas" />
            <label>Filtros:</label>
            <input type="submit" value="Filtrar categoria" name="interfazCategoria" />     
            <input type="submit" value="Filtrar asignatura" name="interfazAsignatura" />
            <input type="submit" value="Filtrar Urgentes" name="filtrarUrgentes" />
            <input type="submit" value="Filtrar Curso" name="interfazCurso" />
            <label>Añadir:</label>
            <input type="submit" value="Insertar tarea" name="interfazInsertarTarea" />
            <input type="submit" value="Insertar Asignatura" name="interfazInsertarAsignatura" /> 
            <label>Eliminar:</label>
            <input type="submit" value="Eliminar tarea" name="interfazEliminarTarea" />
            <input type="submit" value="Vaciar Lista" name="vaciarLista" />
        </form> 
    <main>
    
    <?php

        /**
         * Se espera del evaluador de la aplicación que cree la base de datos
         * y las tablas a través de la interfaz proporcionada para ello
         */
        class BaseDatos{

            public function __construct($nombreBaseDatos){
                
                if( !isset( $_SESSION['baseDatos'] ) ) {
                    $_SESSION['baseDatos']= $this;
                    // Usuario ya creado en la base de datos MySQL en XAMPP: MySQL [Admin]
                    //datos de la base de datos
                    $this->servername = "localhost";
                    $this->username = "DBUSER2020";
                    $this->password = "DBPSWD2020";

                    // Conexión al SGBD local con XAMPP con el usuario creado 
                    $this->db = new mysqli($this->servername,$this->username,$this->password);

                    //comprobamos conexión
                    if($this->db->connect_error) {
                        exit ("<p>ERROR de conexión:".$this->db->connect_error."</p>");  
                    } 
                    
                    //$this->creaBaseDatos($nombreBaseDatos);
                    //$this->verLista();
                    $this->submit();
                } else {
                    $_SESSION['baseDatos']->submit();
                }
   
            }

            public function creaBaseDatos($nombreBaseDatos){

                try{
                    //prepara la sentencia de creación
                    $consultaPre = $this->db->prepare( "CREATE DATABASE IF NOT EXISTS " . $nombreBaseDatos . " COLLATE utf8_spanish_ci");   
                                    
                } catch (Exception $e){
                    die('Error preparando consulta: ' .  $e->getMessage());
                }  

                //Ejecuta la sentencia preparada y comprueba su estado
                if($consultaPre->execute() === TRUE){
                    echo "<p>Base de datos " . $nombreBaseDatos . " creada con éxito</p>";
                } else { 
                    echo "<p>ERROR en la creación de la Base de Datos " . $nombreBaseDatos . ". Error: " . $this->db->error . "</p>";
                }
                $consultaPre->close();

            }

            /**
             * Funcionalidad para crear las tablas y cargar datos en ellas
             */
            public function crearTablas(){

                //selecciono la base de datos BaseDatos para utilizarla
                $this->db->select_db("TODOList");
                
                //Tabla Asignatura
                $crearTablaAsignatura = "CREATE TABLE IF NOT EXISTS Asignatura 
                (id INT NOT NULL AUTO_INCREMENT, 
                nombre VARCHAR(255) NOT NULL, 
                codigo VARCHAR(255) NOT NULL UNIQUE, 
                curso TINYINT UNSIGNED NOT NULL,
                PRIMARY KEY (id) )ENGINE=InnoDB";

                //Tabla Tarea
                $crearTablaTarea = "CREATE TABLE IF NOT EXISTS Tarea 
                (id INT NOT NULL AUTO_INCREMENT, 
                nombre VARCHAR(255) NOT NULL, 
                categoria VARCHAR(255) NOT NULL, 
                dificultad TINYINT UNSIGNED NOT NULL CHECK(dificultad BETWEEN 0 AND 10),
                deadline DATE NOT NULL,
                PRIMARY KEY (id))ENGINE=InnoDB";

                //Tabla TODO
                $crearTablaTODO = "CREATE TABLE IF NOT EXISTS TODO ( 
                idAsignatura INT NOT NULL, 
                idTarea INT NOT NULL UNIQUE, 
                horasEstimadas TINYINT UNSIGNED NOT NULL,
                FOREIGN KEY (idAsignatura) REFERENCES Asignatura ( id ),
                FOREIGN KEY (idTarea) REFERENCES Tarea ( id ),
                PRIMARY KEY (idAsignatura, idTarea) 
                )ENGINE=InnoDB";

                //Creación de las tablas. No se necesita sentencia de preparación porque los metadatos son internos
                if($this->db->query($crearTablaAsignatura) === TRUE){
                    echo "<p>Tabla Asignatura creada con éxito </p>";
                } else { 
                    echo "<p>ERROR en la creación de la tabla. Error : ". $this->db->error . "</p>";
                    exit();
                }

                if($this->db->query($crearTablaTarea) === TRUE){
                    echo "<p>Tabla Tarea creada con éxito </p>";
                } else { 
                    echo "<p>ERROR en la creación de la tabla. Error : ". $this->db->error . "</p>";
                    exit();
                }

                if($this->db->query($crearTablaTODO) === TRUE){
                    echo "<p>Tabla TODO creada con éxito </p>";
                } else { 
                    echo "<p>ERROR en la creación de la tabla. Error : ". $this->db->error . "</p>";
                    exit();
                }

                $this->popularTablas();
            }

            public function cerrarConexion(){
                $this->db->close();
            }

            public function filtrarAsignatura(){

                $this->db->select_db("TODOList");
                
                //Tabla Asignatura
                $consultaPre = $this->db->prepare(
                    "SELECT  Tarea.nombre as tarea, Tarea.categoria, Tarea.deadline, 
                    Tarea.dificultad, Asignatura.nombre, TODO.horasEstimadas 
                    FROM TODO 
                    INNER JOIN Tarea ON TODO.idTarea=Tarea.iD 
                    INNER JOIN Asignatura ON TODO.idAsignatura=Asignatura.id 
                    WHERE idAsignatura = ?");


                $consultaPre->bind_param('s', $_POST["asignatura"] );    

                try{
                    //ejecuta la sentencia
                    $resultado = $consultaPre->execute();
                } catch (Exception $e){
                    echo '<p>No se ha podido realizar la consulta: ' .  $e->getMessage() . '</p>';
                }

                $resultado = $consultaPre->get_result();
    
                if ($resultado->num_rows > 0) {
                        echo "<p>Tareas pendientes: " . $resultado->num_rows . "</p>";
                        echo "<ul>";
                       
                        while($row = $resultado->fetch_assoc()) {
                            echo "<li>" . $row['tarea']." - ". $row['categoria']." - ". $row['dificultad'] . $row['deadline']." - ".$row['horasEstimadas']." - ".$row['nombre']."</li>"; 
                        }
                        echo "</ul>";
                    } else {
                        echo "<p>No hay tareas de esa asignatura.</p>";
                    } 
            }

            private function añadirTODO($idTarea, $idAsignatura, $horas){


                $this->db->select_db("TODOList");
                    
                try{
                    $consultaPre = $this->db->prepare(

                        "INSERT INTO TODO (idAsignatura, idTarea, horasEstimadas) 
                        VALUES ( ?, ?, ?)");   

                } catch (Exception $e){
                    echo '<p>Error preparando consulta: ' .  $e->getMessage() . '</p>';
                }   

                $consultaPre->bind_param('iii', $idAsignatura, $idTarea, $horas );    

                try{
                    $consultaPre->execute();
                    $this->verLista();
                    echo '<p>Fila agregada con éxito: ' .  'Asignatura: ' .  $idAsignatura . ', Tarea: ' .$idTarea . ', Horas: ' . $horas   .'</p>';
                } catch (Exception $e){
                    echo '<p>No se ha podido agregar la fila: ' .  $e->getMessage() . '</p>';
                }

            }

            public function añadirTarea(){

                $this->db->select_db("TODOList");
                    
                try{
                    $consultaPre = $this->db->prepare("INSERT INTO Tarea (nombre, categoria, dificultad, deadline) VALUES ( ?, ?, ?, ?)");                   
                } catch (Exception $e){
                    echo '<p>Error preparando consulta: ' .  $e->getMessage() . '</p>';
                }   
            
                //Comprobación de campos:
                if($_POST["nombre"] == NULL){
                    echo "<p>El campo nombre no puede ser nulo</p>";
                    exit();
                }
                if($_POST["categoria"] == NULL){
                    echo "<p>El campo categoria no puede ser nulo</p>";
                    exit();
                } 
                if($_POST["dificultad"] == NULL) {
                    echo "<p>El campo dificultad no puede ser nulo</p>";
                    exit();
                }
                
                if($_POST["deadline"] == NULL){
                    echo "<p>El campo deadline no puede ser nulo</p>";
                    exit();
                } 

                if($_POST["asignatura"] == NULL){
                    echo "<p>El campo idAsignatura no puede ser nulo</p>";
                    exit();
                }
 
                if($_POST["horas"] == NULL) {
                    echo "<p>El campo horasEstimadas no puede ser nulo</p>";
                    exit();
                } 

                $consultaPre->bind_param('ssis', 
                    $_POST["nombre"], $_POST["categoria"],$_POST["dificultad"], $_POST["deadline"]);    

                try{
                    $consultaPre->execute();
                    echo '<p>Fila agregada con éxito: ' .  'Nombre: ' .  $_POST["nombre"] . ', Categoria: ' . $_POST["categoria"] . ', Dificultad: ' . $_POST["dificultad"] . ', Deadline: ' . $_POST["deadline"] .'</p>';
                } catch (Exception $e){
                    echo '<p>No se ha podido agregar la fila: ' .  $e->getMessage() . '</p>';
                }
                $consultaPre->close();

                $idTarea = "SELECT id FROM Tarea ORDER BY id DESC LIMIT 1";
                $result = $this->db->query($idTarea);
                $this->añadirTODO($result->fetch_assoc()["id"], $_POST["asignatura"], $_POST["horas"]);
            }

            public function añadirAsignatura(){

                $this->db->select_db("TODOList");
                    
                try{
                    $consultaPre = $this->db->prepare(
                        "INSERT INTO Asignatura (nombre, codigo, curso) 
                        VALUES ( ?, ?, ?)");                   
                } catch (Exception $e){
                    echo '<p>Error preparando consulta: ' .  $e->getMessage() . '</p>';
                }   
            
                //Comprobación de campos:
                if($_POST["nombre"] == NULL){
                    echo "<p>El campo nombre no puede ser nulo</p>";
                    exit();
                }
                if($_POST["codigo"] == NULL){
                    echo "<p>El campo codigo no puede ser nulo</p>";
                    exit();
                } 
                if($_POST["curso"] == NULL) {
                    echo "<p>El campo curso no puede ser nulo</p>";
                    exit();
                } 

                $consultaPre->bind_param('ssi', 
                    $_POST["nombre"], $_POST["codigo"],$_POST["curso"] );    

                try{
                    $consultaPre->execute();
                    echo '<p>Fila agregada con éxito: ' .  'Nombre: ' .  $_POST["nombre"] . ', Código: ' . $_POST["codigo"] . ', Curso: ' . $_POST["curso"]  .'</p>';
                    $this->verAsignaturas();
                } catch (Exception $e){
                    echo '<p>No se ha podido agregar la fila: ' .  $e->getMessage() . '</p>';
                }
                $consultaPre->close();
            }

            private function popularTablas(){

                $this->populaAsignaturas();
                $this->populaTareas();
                $this->populaTODOs();
            }

            private function populaAsignaturas(){

                try{

                    $consultaPre = $this->db->prepare( "INSERT INTO Asignatura (nombre, codigo, curso) VALUES 
                    ( 'Estructuras de datos', 'GIISOF01-2-003', 2),
                    ( 'Ondas y Electromagnetismo', 'GIISOF01-1-005', 1),
                    ( 'Software y Estándares para la Web', 'GIISOF01-3-002', 3),
                    ( 'Fundamentos de Informática', 'GIISOF01-1-001', 1),
                    ( 'Diseño del Software', 'GIISOF01-3-004', 3),
                    ( 'Arquitectura de Computadores', 'GIISOF01-2-002', 2),
                    ( 'Ingeniería de Requisitos', 'GIISOF01-4-002', 4)");   
                                    
                } catch (Exception $e){
                    die('Error preparando consulta: ' .  $e->getMessage());
                }  

                if($consultaPre->execute() === TRUE){
                    echo "<p>Tabla asignatura populada con éxito</p>";
                } else { 
                    echo "<p>ERROR en la carga de datos para la tabla Asignatura</p>";
                }
                $consultaPre->close();

            }

            private function populaTareas(){
                
                try{

                    $consultaPre = $this->db->prepare( "INSERT INTO Tarea (nombre, categoria, dificultad, deadline) VALUES 
                    ( 'Editor de figuras', 'Practica', 2, '2020-10-05'),
                    ( 'API Google Maps', 'Practica', 8, '2020-11-15'),
                    ( 'Implementar un logger', 'Practica', 9, '2020-11-29' ),
                    ( 'Wireframe', 'Seminario', 5, '2020-11-10'),
                    ( 'Bitácora', 'Seminario', 7, '2020-12-18'),
                    ( 'AWS máquina', 'Seminario', 6, '2020-10-15'),
                    ( 'Test usabilidad', 'Practica', 4, '2020-12-06')");   
                                    
                } catch (Exception $e){
                    die('Error preparando consulta: ' .  $e->getMessage());
                }  

                if($consultaPre->execute() === TRUE){
                    echo "<p>Tabla tarea populada con éxito</p>";
                } else { 
                    echo "<p>ERROR en la carga de datos para la tabla Tarea</p>";
                }
                $consultaPre->close();

            }

            private function populaTODOs(){
                
                try{
                    $consultaPre = $this->db->prepare( 
                        "INSERT INTO TODO (idAsignatura, idTarea, horasEstimadas) 
                    VALUES 
                    ( 5, 1, 13),
                    ( 5, 2, 6),
                    ( 5, 3, 3 ),
                    ( 4, 4, 1),
                    ( 3, 5, 35),
                    ( 3 ,6, 1),
                    ( 7, 7, 7)");   
                                    
                } catch (Exception $e){
                    die('Error preparando consulta: ' .  $e->getMessage());
                }  

                if($consultaPre->execute() === TRUE){
                    echo "<p>Tabla TODOs populada con éxito</p>";
                    $this->verLista();
                } else { 
                    echo "<p>ERROR en la carga de datos para la tabla TODOs:</p>";
                }
                $consultaPre->close();
            }

            public function vaciarLista(){

                $this->db->select_db("TODOList");

                //Vacía la tabla TODO
                $vaciarLista = "DELETE FROM TODO";

                if($this->db->query($vaciarLista) !== TRUE){
                    echo "<p>ERROR para vaciar lista. Error : ". $this->db->error . "</p>";
                    exit();
                }
        

                //Vacía la tabla Tareas
                $vaciarTareas = "DELETE FROM Tarea";

                if($this->db->query($vaciarTareas) === TRUE){
                    echo "<p>Lista Tareas vacía </p>";
                } else { 
                    echo "<p>ERROR para vaciar lista. Error : ". $this->db->error . "</p>";
                    exit();
                }

            }

            public function interfazCategoria(){
                echo "<form method='post' action='#'>
                <p>Categoría: <input type='text' name='categoria' /> </p>
                <input type='submit' value='Filtrar Categoría' name='filtrarCategoria' />
                </form>";
                $this->verLista();

            }

            public function interfazAsignatura(){
                echo '<form method="post" action="#">
                <p>ID Asignatura: <input type="text" name="asignatura" /> </p>
                <input type="submit" value="Filtrar Asignatura" name="filtrarAsignatura" />
                </form>';
                $this->verLista();
            }

            public function interfazCurso(){
                echo '<form method="post" action="#">
                <p>Curso: <input type="number" name="curso" /> </p>
                <input type="submit" value="Filtrar Curso" name="filtrarCurso" />
                </form>';
                $this->verLista();
            }

            public function interfazInsertarAsignatura(){
                echo '<form method="post" action="#">
                <p>Nombre: <input type="text" name="nombre" /> </p>
                <p>Código: <input type="text" name="codigo" /></p>
                <p>Curso: <input type="number" name="curso" min="0" /> </p>
                <input type="submit" value="Insertar Asignatura" name="submitAsignatura" />
                </form>';
                $this->verLista();
            }

            public function interfazInsertarTarea(){

                echo '<form method="post" action="#">
                <p>Nombre: <input type="text" name="nombre" /> </p>
                <p>Categoría: <input type="text" name="categoria" /></p>
                <p>Dificultad: <input type="range" name="dificultad" min="0" max="10" step="1" /> </p>
                <p>Deadline: <input type="date" name="deadline" /></p>
                <p>Asignatura(id): <input type="number" name="asignatura" /></p>
                <p>Estimación de horas: <input type="number" name="horas" /></p>
                <input type="submit" value="Insertar Tarea" name="submitTarea" />
                </form>';
                $this->verLista();
            }

            public function interfazEliminarTarea(){
                echo '<form method="post" action="#">
                <p>Tarea(id): <input type="text" name="tarea" /> </p>
                <input type="submit" value="Eliminar Tarea" name="eliminarTarea" />
                </form>';
                $this->verLista();
            }

            public function filtrarCategoria(){

                $this->db->select_db("TODOList");
 
                $consultaPre = $this->db->prepare(
                    "SELECT * FROM Tarea 
                    INNER JOIN TODO ON Tarea.iD=TODO.idTarea 
                    WHERE categoria = ?");

                $consultaPre->bind_param('s',$_POST["categoria"] );    

                try{
                    $resultado = $consultaPre->execute();
                    //$this->verLista();
                } catch (Exception $e){
                    echo '<p>No se ha podido filtrar por categoría: ' .  $e->getMessage() . '</p>';
                }

                $resultado = $consultaPre->get_result();
    
                if ($resultado->num_rows > 0) {
                        // Mostrar los datos en un lista
                        echo "<p>Tareas pendientes por categoría: </p>";
                        echo "<p>Número de filas = " . $resultado->num_rows . "</p>";
                        echo "<ul>";
                        while($row = $resultado->fetch_assoc()) {
                            echo "<li>". $row['id'] . " - " . $row['nombre']." - ". $row['categoria']." - ". $row['dificultad'] . $row['deadline']." - ".$row['horasEstimadas']."</li>"; 
                        }
                        echo "</ul>";
                    } else {
                        echo "<p>No hay tareas en la categoría " . $_POST["categoria"]  . "</p>";
                    } 
                $consultaPre->close();
            }  


            public function filtrarUrgentes(){

                $this->db->select_db("TODOList");
                
                $consultaPre = $this->db->prepare(
                    "SELECT * FROM TODO 
                    JOIN Tarea ON TODO.idTarea=Tarea.id 
                    ORDER BY deadline");   

                try{
                    $resultado = $consultaPre->execute();
                } catch (Exception $e){
                    echo '<p>No se ha podido realizar la consulta: ' .  $e->getMessage() . '</p>';
                }

                $resultado = $consultaPre->get_result();
     
                if ($resultado->num_rows > 0) {
                        // Mostrar los datos en un lista
                        echo "<p>Tareas más urgentes: " . $resultado->num_rows . "</p>";
                        echo "<ul>";
                        while($row = $resultado->fetch_assoc()) {
                            echo "<li>". $row['id'] . " - " . $row['nombre']." - ". $row['categoria']." - Dificultad: ". $row['dificultad'] ." - ". $row['deadline']." - ".$row['horasEstimadas']." horas estimadas</li>"; 
                        }
                        echo "</ul>";
                    } else {
                        echo "<p>Tabla vacía. Número de filas = " . $resultado->num_rows . "</p>";
                    }
                $consultaPre->close(); 

            }

            public function filtrarCurso(){

                $this->db->select_db("TODOList");
 
                $consultaPre = $this->db->prepare(
                    "SELECT * FROM Tarea 
                    INNER JOIN TODO ON Tarea.iD=TODO.idTarea 
                    INNER JOIN Asignatura ON TODO.idAsignatura=Asignatura.id
                    WHERE curso = ?");

                $consultaPre->bind_param('s',$_POST["curso"] );    

                try{
                    $resultado = $consultaPre->execute();
                    //$this->verLista();
                } catch (Exception $e){
                    echo '<p>No se ha podido filtrar por curso: ' .  $e->getMessage() . '</p>';
                }

                $resultado = $consultaPre->get_result();
    
                if ($resultado->num_rows > 0) {
                        // Mostrar los datos en un lista
                        echo "<p>Tareas pendientes del curso: " . $_POST["curso"].  "</p>";
                        echo "<p>Número de filas = " . $resultado->num_rows . "</p>";
                        echo "<ul>";
                        while($row = $resultado->fetch_assoc()) {
                            echo "<li>". $row['id'] . " - " . $row['nombre']." - ". $row['categoria']." - ". $row['dificultad'] . $row['deadline']." - ".$row['horasEstimadas']."</li>"; 
                        }
                        echo "</ul>";
                    } else {
                        echo "<p>No hay tareas en el curso " . $_POST["curso"]  . "</p>";
                    } 
                $consultaPre->close();
            }

            public function verAsignaturas(){

                $this->db->select_db("TODOList");
                
                //Recupera los campos de interés para mostrar al usuario
                $consultaPre = $this->db->prepare(
                    "SELECT * FROM Asignatura");   

                try{
                    $resultado = $consultaPre->execute();
                } catch (Exception $e){
                    echo '<p>No se ha podido realizar la consulta "VerAsignaturas:" ' .  $e->getMessage() . '</p>';
                }

                $resultado = $consultaPre->get_result();
                    
                // compruebo los datos recibidos     
                if ($resultado->num_rows > 0) {
                        // Mostrar los datos en un lista
                        echo "<p>Asignaturas: ". $resultado->num_rows . "</p>";
                        echo "<ul>";
                        while($row = $resultado->fetch_assoc()) {
                            echo "<li>". $row['id'] . " - " . $row['nombre']." - ". $row['codigo']." - ".$row['curso']." º</li>"; 
                        }
                        echo "</ul>";
                    } else {
                        echo "<p>Sin asignaturas. Número de filas = " . $resultado->num_rows . "</p>";
                    }
                $consultaPre->close(); 
            }

            public function verLista(){

                $this->db->select_db("TODOList");
                
                //Recupera los campos de interés para mostrar al usuario
                $consultaPre = $this->db->prepare(
                    "SELECT Tarea.id as numTarea, 
                        Tarea.nombre as nombreTODO, 
                        Asignatura.nombre as nombreAsignatura, 
                        categoria, dificultad, deadline, horasEstimadas 
                    FROM TODO 
                    JOIN Tarea ON TODO.idTarea=Tarea.id 
                    JOIN Asignatura ON TODO.idAsignatura=Asignatura.id");   

                try{
                    $resultado = $consultaPre->execute();
                } catch (Exception $e){
                    echo '<p>No se ha podido realizar la consulta "VerLista:" ' .  $e->getMessage() . '</p>';
                }

                $resultado = $consultaPre->get_result();
                    
                // compruebo los datos recibidos     
                if ($resultado->num_rows > 0) {
                        // Mostrar los datos en un lista
                        echo "<p>Tareas pendientes: ". $resultado->num_rows . "</p>";
                        echo "<ul>";
                        while($row = $resultado->fetch_assoc()) {
                            echo "<li>". $row['numTarea'] . " - " . $row['nombreTODO']." - ". $row['nombreAsignatura']." - ". $row['categoria'] ." - Dificultad: ". $row['dificultad'] ." - ". $row['deadline']." - ".$row['horasEstimadas']." horas estimadas</li>"; 
                        }
                        echo "</ul>";
                    } else {
                        echo "<p>Tabla vacía. Número de filas = " . $resultado->num_rows . "</p>";
                    }
                $consultaPre->close(); 

            }

            private function eliminarTODO($idTarea){

                $this->db->select_db("TODOList");

                $consultaPre = $this->db->prepare("DELETE FROM TODO WHERE idTarea = ?");
                $consultaPre->bind_param('i', $idTarea );    

                try{
                    $resultado = $consultaPre->execute();
                } catch (Exception $e){
                    echo '<p>No se ha podido eliminar el TODO: ' .  $e->getMessage() . '</p>';
                }
                $consultaPre->close();
            }

            public function eliminarTarea(){

                //Usuario especifíca el id de la tarea que desea eliminar de la lista
                $idTarea = $_POST["tarea"];

                //Eliminar la entrada correspondiente de la tabla TODO
                $this->eliminarTODO($idTarea);


                $this->db->select_db("TODOList");
                
                //Eliminar registro de la tabla Tarea
                $consultaPre = $this->db->prepare("DELETE FROM Tarea WHERE id = ?");
                $consultaPre->bind_param('i', $idTarea );    

                try{
                    $resultado = $consultaPre->execute();
                    echo '<p>Tarea ' . $idTarea . ' eliminada con éxito</p>';
                    $this->verLista();
                } catch (Exception $e){
                    echo '<p>No se ha podido eliminar la tarea: ' .  $e->getMessage() . '</p>';
                }
                $consultaPre->close();
            }

            public function submit(){
                if (count($_POST)>0) 
                {   
                    // Llama a cada método del objeto $baseDatos 
                    // dependiendo de qué formulario ha hecho el submit
                    if(isset($_POST["creaTablas"])) $_SESSION['baseDatos']->crearTablas();
                    if(isset($_POST["creaBaseDatos"])) $_SESSION['baseDatos']->creaBaseDatos('TODOList');
                    if(isset($_POST["interfazInsertarTarea"])) $_SESSION['baseDatos']->interfazInsertarTarea();
                    if(isset($_POST["submitTarea"])) $_SESSION['baseDatos']->añadirTarea();
                    if(isset($_POST["submitTODO"])) $_SESSION['baseDatos']->añadirTODO();
                    if(isset($_POST["submitAsignatura"])) $_SESSION['baseDatos']->añadirAsignatura();
                    if(isset($_POST["interfazInsertarAsignatura"])) $_SESSION['baseDatos']->interfazInsertarAsignatura();
                    if(isset($_POST["filtrarAsignatura"])) $_SESSION['baseDatos']->filtrarAsignatura();
                    if(isset($_POST["interfazAsignatura"])) $_SESSION['baseDatos']->interfazAsignatura();
                    if(isset($_POST["verAsignaturas"])) $_SESSION['baseDatos']->verAsignaturas();
                    if(isset($_POST["filtrarCategoria"])) $_SESSION['baseDatos']->filtrarCategoria();
                    if(isset($_POST["interfazCategoria"])) $_SESSION['baseDatos']->interfazCategoria();

                    if(isset($_POST["filtrarDuracion"])) $_SESSION['baseDatos']->filtrarDuracion();
                    if(isset($_POST["filtrarCurso"])) $_SESSION['baseDatos']->filtrarCurso();
                    if(isset($_POST["interfazCurso"])) $_SESSION['baseDatos']->interfazCurso();
                    if(isset($_POST["filtrarUrgentes"])) $_SESSION['baseDatos']->filtrarUrgentes();
                    if(isset($_POST["contarTareas"])) $_SESSION['baseDatos']->contarTareas();
                    if(isset($_POST["verLista"])) $_SESSION['baseDatos']->verLista();
                    if(isset($_POST["vaciarLista"])) $_SESSION['baseDatos']->vaciarLista();
                    if(isset($_POST["eliminarTarea"])) $_SESSION['baseDatos']->eliminarTarea();
                    if(isset($_POST["interfazEliminarTarea"])) $_SESSION['baseDatos']->interfazEliminarTarea();

                }
            }
        }
        
        $baseDatos = new BaseDatos("TODOList");

            ?>
    </main>
</body>
</html>