<?php 
    include_once("Connection.php");
    class Admin
    {
        public $isAdmin;
        public $APIKey;

        public function __construct()
        {
            $this->isAdmin = isset($_SESSION['username']) && $_SESSION['username']=='admin';
            $this->APIKey ="b27f9641";
        }

        public function getQueue()
        {
            $temp = new Connection();
            $conn = $temp->getConnection();

            $sql = "SELECT * FROM preguntas WHERE estado = 'cola' ORDER BY fecha ASC";

            $result = mysqli_query($conn,$sql);

            if($result && $result->num_rows>0)
            {
                $out = '<table class ="table table-hover sa_table"><tbody>';
                // $out .= '<thead><tr><th>&nbsp;</th><th>Usuario</th><th>Reseña</th><th>Razón</th>
                // <th>&nbsp;</th><th>Película</th><th>&nbsp;</th><th>&nbsp;</th></tr></thead>';
                while($data = mysqli_fetch_assoc($result))
                {
                    $out .= '<tr>';
                    $out .= '<td>'.$data['pregunta'].'</td>';
                    $arg = "'".$data['clave']."'";
                    $out .= '<td><a title="Eliminar pregunta" onclick="deleteQuestion('.$arg.')">
                    <i class="fas fa-trash"></i></a></td>';
                    $out .= '</tr>';
                }
                $out .= '</tbody></table>';
                return $out;
            }
            else 
            {
                return "<h4>Por el momento no hay preguntas en espera. Agregue preguntas en la sección agregar</h4>";
            }
        }

        public function getReports()
        {
            $temp = new Connection();
            $conn = $temp->getConnection();

            $sql = "SELECT usuario.imagen as img, review.texto as texto, review.usuario as user, review.pelicula as movie, 
            review_reporte.razon as razon, review.clave as clave1, review_reporte.clave as clave2  
            FROM review, review_reporte,usuario WHERE review_reporte.review = review.clave AND 
            review.usuario = usuario.username";

            $result = mysqli_query($conn,$sql);

            if($result && $result->num_rows>0)
            {
                $out = '<table class ="table table-hover sa_table"><tbody>';
                $out .= '<thead><tr><th>&nbsp;</th><th>Usuario</th><th>Reseña</th><th>Razón</th>
                <th>&nbsp;</th><th>Película</th><th>&nbsp;</th><th>&nbsp;</th></tr></thead>';
                while($data = mysqli_fetch_assoc($result))
                {
                    $out .= '<tr>';
                    $user = $data['user'];
                    $hr = "<a style='color: white;' href = 'profile.php?user=$user'>";

                    $out .= "<td>$hr<img src='".$data['img']."'></a></td>";
                    $out .= "<td>$hr".$data['user']."</a></td>";

                    $out .= "<td>".$data['texto']."</td>";
                    $out .= "<td>".$data['razon']."</td>";

                    $url = "http://www.omdbapi.com/?apikey=$this->APIKey&i=" . $data['movie'];
                    $content = file_get_contents($url);
                    $body = json_decode($content, true);

                    $hr = "<a style='color: white;' href = 'movie.php?id=".$data['movie']."'>";
                    $out .= "<td>$hr<img src='".$body['Poster']."'></a></td>";
                    $out .= "<td>$hr".$body['Title']." (".$body['Year'].") </a></td>";

                    $out .= '<td><a title="Borrar reseña" onclick="deleteRev('."'".$data['clave1']."'".')"><i class="fas fa-ban"></i></a></td>';
                    $out .= '<td><a title="Descartar reporte" onclick="discardRev('."'".$data['clave2']."'".')"><i class="fas fa-trash-restore-alt"></i></a></td>';
                    $out .= '</tr>';
                }
                $out .= '</tbody></table>';
                return $out;
            }
            else 
            {
                return "<h4>¡Genial! No hay reportes pendientes</h4>";
            }
        }
    }
?>