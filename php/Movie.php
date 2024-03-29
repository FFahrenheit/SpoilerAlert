<?php
require_once('Connection.php');
// $a = microtime(true);
// $movie = new Movie();
// $movie->advancedSearch("star+wars","Lucas");
// echo "<br>". (microtime(true) - $a);
class Movie
{
  public $APIKey;
  public $vista = false;
  public function __construct()
  {
    $this->APIKey = "b27f9641";
  }

  public function advancedSearch($title, $director, $year = -1)
  {
    $app = ($year != -1) ? "&y=" . $_GET['year'] : "";
    $url = "https://www.omdbapi.com/?apikey=$this->APIKey&s=$title&type=movie$app";
    $content = file_get_contents($url);
    $json = json_decode($content, true);
    if ($json['Response'] == 'True') {
      /* Aquí empieza el gran algoritmo de nuestras vidas */
      $out = "";
      $results = $json['totalResults'];
      $matched = 0;
      // echo ((int) ($results / 10))."<br>";
      for ($i = 0; $i < ((int) ($results / 10))+ 1 && $i<3; $i++) {   //Descomentar && $i<3 para full resultados {{DANGER!!}}
        if ($i != 0) {
          $alURL = $url . "&page=" . ($i + 1);
          $alContent = file_get_contents($alURL);
          $alResult = json_decode($alContent, true);
        } else {
          $alResult = $json;
        }
        foreach ($alResult['Search'] as $result) {
          $Purl = "https://www.omdbapi.com/?apikey=$this->APIKey&i=" . $result['imdbID'] . "&plot=short";
          $Pcontent = file_get_contents($Purl);
          $Pjson = json_decode($Pcontent, true);
          similar_text(strtolower($Pjson['Director']), strtolower($director), $perc);
          // echo $perc . " " . $director . " vs " . $Pjson['Director'] . "<br>";
          if (strpos($Pjson['Director'],$director) || $perc > 50) {
            $matched++;
            $str = '<div class="card mb-3" style="max-width: 70%;">
                      <div class="row no-gutters bg-dark">
                        <div class="col-md-4" style="max-width: 100px">
                          <img src="';
            $str .= ($Pjson['Poster'] == "N/A") ? "../../img/poster.jpg" : $Pjson['Poster'];
            $str .=    '" class="card-img" alt="' . $Pjson['Title'] . '">
                        </div>
                        <div class="col-md-8">
                          <div class="card-body">
                          <a style="text-decoration: none;"href="movie.php?id=' . $Pjson['imdbID'] . '">
                          <h5 class="card-title text-warning">' . $Pjson['Title'] . '</h5>
                          </a>
                            <p class="card-text text-warning">Año: ' . $Pjson['Year'] . '</p>
                            <p class="card-text text-warning">Género(s): ' . $Pjson['Genre'] . '</p>
                            <p hidden class="card-text text-warning">' . $Pjson['Plot'] . '</p>
                          </a>
                          </div>
                        </div>
                        <a class="btn btn-warning sa_button" href="movie.php?id=' . $Pjson['imdbID'] . '">Conocer más</a>
                      </div>
                    </div>';
            $out .= $str;
          }
        }
      }
      $out = '<span class="text-light">Se han encontrado ' . $matched . ' resultado(s).</span>
            <br><p> Nueva búsqueda:</p>' . $this->getSearcher(str_replace("+"," ",$title)) . $out;
      return $out;
    } else if ($json['Response'] == "False") {
      if ($json['Error'] == "Too many results.") {
        echo '<p class="text-light">La búsqueda ha arrojado muchos resultados, por favor sea más específico o filtre su búsqueda por año</p>';
        echo $this->getSearcher(str_replace("+"," ",$title));
      } else if ($json['Error'] == "Movie not found!") {
        echo '<span class="text-light">No se han encontrado resultados con la búsqueda, intente de nuevo.</span>';
        echo $this->getSearcher(str_replace("+"," ",$title));
      } else {
        echo '<span class="text-light"> Error de API: ' . $json['Error'] . "</span>";
      }
    } else {
      echo "Error desconocido...";
    }
  }

  public function getLikes($movie)
  {
    $temp = new Connection();
    $conn = $temp->getConnection();
    $sql = "SELECT COUNT(*) as cont FROM likes WHERE pelicula = '$movie'";
    $result = mysqli_query($conn, $sql);
    if ($result && $result->num_rows > 0) {
      $body = mysqli_fetch_assoc($result);
      return '<h3><i class="fas fa-thumbs-up"></i>Me gustas: <span class="text-light">' . $body['cont'] . ' </span><span class="text-light"></span></h3>';
    }
    return '<h3><i class="fas fa-thumbs-up"></i>Me gustas: <span class="text-light">0</span><span class="text-light"></span></h3>';
  }

  public function getFavorites($movie)
  {
    $temp = new Connection();
    $conn = $temp->getConnection();
    $sql = "SELECT COUNT(*) as likes FROM favoritas WHERE pelicula = '$movie'";
    $result = mysqli_query($conn, $sql);
    if ($result && $data = mysqli_fetch_assoc($result)) {
      return '<h3><i class="fas fa-star"></i>En favoritos: <span class="text-light">' . $data['likes'] . '</span></h3>';
    } else {
      return '<h3><i class="fas fa-star"></i>En favoritos: <span class="text-light">0</span></h3>';
    }
  }

  public function getRatings($movie)
  {
    $temp = new Connection();
    $conn = $temp->getConnection();
    $cont = 0;
    $acum = 0;
    $sql = "SELECT valor FROM calificacion WHERE pelicula = '$movie'";
    $result = mysqli_query($conn, $sql);
    if ($result && $result->num_rows > 0) {
      while ($data = mysqli_fetch_assoc($result)) {
        $cont++;
        $acum += $data['valor'];
      }
      return '<h3><i class="fas fa-globe-americas"></i>Calificación global: <span class="text-light"> ' . round(($acum / $cont), 2) . '/5</span></h3>';
    } else {
      return '<h3><i class="fas fa-globe-americas"></i>Calificación global: <span class="text-light"> N/A </span></h3>';
    }
  }

  public function getMyRating($movie)
  {
    $temp = new Connection();
    $conn = $temp->getConnection();
    if (isset($_SESSION['username'])) {
      $user = $_SESSION['username'];
    } else {
      return "";
    }
    $sql = "SELECT valor as likes FROM calificacion WHERE pelicula = '$movie' AND usuario = '$user'";
    $result = mysqli_query($conn, $sql);
    if ($result && $data = mysqli_fetch_assoc($result)) {
      return '<h3><i class="fas fa-user-check"></i>Mi calificación: <span class="text-light">' . $data['likes'] . ' <i class="fas fa-star"></i></span></h3>';
    } else {
      return '<h3><i class="fas fa-user-check"></i>Mi calificación: <span class="text-light"> Aún no calificada</span></h3>';
    }
  }
  public function getWatched($movie)
  {
    $temp = new Connection();
    $conn = $temp->getConnection();
    $sql = "SELECT COUNT(*) as likes FROM vistas WHERE pelicula = '$movie'";
    $result = mysqli_query($conn, $sql);
    if ($result && $data = mysqli_fetch_assoc($result)) {
      return '<h3><i class="fas fa-eye"></i>Vistas: <span class="text-light">' . $data['likes'] . '</span></h3>';
    } else {
      return '<h3><i class="fas fa-eye"></i></i>Vistas: <span class="text-light">0</span></h3>';
    }
  }

  public function getWatchlist($movie)
  {
    $temp = new Connection();
    $conn = $temp->getConnection();
    $sql = "SELECT COUNT(*) as likes FROM watchlist WHERE pelicula = '$movie'";
    $result = mysqli_query($conn, $sql);
    if ($result && $data = mysqli_fetch_assoc($result)) {
      return '<h3><i class="fas fa-user-clock"></i>Quieren verla: <span class="text-light">' . $data['likes'] . '</span></h3>';
    } else {
      return '<h3><i class="fas fa-user-clock"></i></i>Quieren verla: <span class="text-light">0</span></h3>';
    }
  }

  public function getWatch()
  {
    return "','$this->vista";
  }

  public function getIcons($movie)
  {
    $c = new Connection();
    $conn = $c->getConnection();
    if (isset($_SESSION['username'])) {
      $user = $_SESSION['username'];
    } else {
      return "";
    }
    $icons = "";
    $sql = "SELECT * FROM favoritas WHERE usuario = '$user' AND pelicula = '$movie'";
    $result = mysqli_query($conn, $sql);
    if ($result && $result->num_rows > 0) {
      $icons .= ' <i title="Favorita" class="fas fa-star"></i> ';
    }
    $sql = "SELECT * FROM watchlist WHERE usuario = '$user' AND pelicula = '$movie'";
    $result = mysqli_query($conn, $sql);
    if ($result && $result->num_rows > 0) {
      $icons .= ' <i title="Por ver" class="far fa-clock"></i> ';
    }
    $sql = "SELECT * FROM vistas WHERE usuario = '$user' AND pelicula = '$movie'";
    $result = mysqli_query($conn, $sql);
    if ($result && $result->num_rows > 0) {
      $this->vista = true;
      $icons .= ' <i title="Vista" class="far fa-eye"></i> ';
    }
    $sql = "SELECT * FROM likes WHERE usuario = '$user' AND pelicula = '$movie'";
    $result = mysqli_query($conn, $sql);
    if ($result && $result->num_rows > 0) {
      $icons .= ' <i title="Me gusta" class="fas fa-thumbs-up"></i> ';
    }
    return $icons;
  }

  public function getCard($movie)
  {
    $url = "http://www.omdbapi.com/?apikey=$this->APIKey&i=" . $movie['imdbID'];
    $content = file_get_contents($url);
    $json = json_decode($content, true);
    $title = $movie['Title'] . "(" . $movie['Year'] . ")";
    $str = '<div class="card mb-3" style="max-width: 70%;">
            <div class="row no-gutters bg-dark">
              <div class="col-md-4" style="max-width: 100px">
                <img src="';
    $str .= ($movie['Poster'] == "N/A") ? "../../img/poster.jpg" : $movie['Poster'];
    $str .=    '" class="card-img" alt="' . $title . '">
              </div>
              <div class="col-md-8">
                <div class="card-body">
                <a style="text-decoration: none;"href="movie.php?id=' . $movie['imdbID'] . '">
                <h5 class="card-title text-warning">' . $movie['Title'] . '</h5>
                </a>
                  <p class="card-text text-warning">Año: ' . $movie['Year'] . '</p>
                  <p class="card-text text-warning">Género(s): ' . $json['Genre'] . '</p>
                  <p hidden class="card-text text-warning">' . $json['Plot'] . '</p>
                </a>
                </div>
              </div>
              <a class="btn btn-warning sa_button" href="movie.php?id=' . $movie['imdbID'] . '">Conocer más</a>
            </div>
          </div>';
    return $str;
  }

  public function getNavigator($page, $total, $movie)
  {
    $url = "search.php?title=$movie&page=";
    if ($total <= 10) {
      return "";
    } else if ($total > 10 && $total <= 20) {
      $nav = "";
      $nav .= '<div class="sa_dark"><nav aria-label="Paginas" >
                <ul class="pagination justify-content-center">';
      for ($i = 0; $i < ($total / 10); $i++) {
        if ($page != ($i + 1)) {
          $nav .= '<li class="page-item active">
                        <a class="page-link bg-warning text-dark" href="' . $url . ($i + 1) . '">' . ($i + 1) . '</a>
                        </li>';
        } else {
          $nav .= '<li class="page-item active bg-warning text-dark">
                        <span class="page-link bg-secondary text-dark">
                        ' . ($i + 1) . '
                            <span class="sr-only">(current)</span>
                      </span>
                        </li>';
        }
      }
      $nav .=  '</ul>
                </nav></div>';
      return $nav;
    } else if ($page == 1) {
      return '<div class="sa_dark"><nav aria-label="Paginas" >
                <ul class="pagination justify-content-center">
                  <li class="page-item bg-warning text-dark text-muted">
                    <span class="page-link text-muted bg-warning text-dark">Anterior</span>
                  </li>
                  <li class="page-item active bg-warning text-dark">
                  <span class="page-link bg-secondary text-dark">
                  ' . $page . '
                  <span class="sr-only">(current)</span>
                </span>
                  </li>
                  <li class="page-item active">
                  <a class="page-link bg-warning text-dark" href="' . $url . ($page + 1) . '">' . ($page + 1) . '</a>

                  </li>
                  <li class="page-item"><a class="page-link bg-warning text-dark" href="' . $url . ($page + 2) . '">' . ($page + 2) . '</a></li>
                  <li class="page-item">
                    <a class="page-link bg-warning text-dark" href="' . $url . ($page + 1) . '">Siguiente</a>
                  </li>
                </ul>
              </nav></div>';
    } else if ($page * 10 >= $total) {
      return '<div class="sa_dark"><nav aria-label="Paginas" >
                <ul class="pagination justify-content-center">
                  <li class="page-item bg-warning text-dark text-muted">
                  <a class="page-link bg-warning text-dark" href="' . $url . ($page - 1) . '">Anterior</a>
                  </li>
                  <li class="page-item active bg-warning text-dark">
                  <a class="page-link bg-warning text-dark" href="' . $url . ($page - 2) . '">' . ($page - 2) . '</a>
                  </li>
                  <li class="page-item active">
                  <a class="page-link bg-warning text-dark" href="' . $url . ($page - 1) . '">' . ($page - 1) . '</a>

                  </li>
                  <li class="page-item">
                  <span class="page-link bg-secondary text-dark">
                  ' . $page . '
                  <span class="sr-only">(current)</span>
                </span>
                </li>
                  <li class="page-item">
                  <span class="page-link text-muted bg-warning text-dark">Siguiente</span>
                  </li>
                </ul>
              </nav></div>';
    } else {
      return '<nav aria-label="Paginas">
                <ul class="pagination justify-content-center">
              <li class="page-item bg-warning text-dark">
              <a class="page-link bg-warning text-dark" href="' . $url . ($page - 1) . '">Anterior</a>
              </li>
              <li class="page-item bg-warning text-dark"><a class="page-link bg-warning text-dark" href="' . $url . ($page - 1) . '">' . ($page - 1) . '</a></li>
              <li class="page-item active">
                <span class="page-link bg-secondary text-dark">
                  ' . $page . '
                  <span class="sr-only">(current)</span>
                </span>
              </li>
              <li class="page-item"><a class="page-link bg-warning text-dark" href="' . $url . ($page + 1) . '">' . ($page + 1) . '</a></li>
              <li class="page-item">
                <a class="page-link bg-warning text-dark" href="' . $url . ($page + 1) . '">Siguiente</a>
              </li>
            </ul>
          </nav>';
    }
  }

  public function getSearcher($title)
  {
    return
      '<form id="formulario" novalidate>
              <div class="form-row align-items-center">
                <div class="col-auto">
                  <label class="sr-only" for="inlineFormInputGroup">Título o palabra clave</label>
                  <div class="input-group mb-2">
                    <div class="input-group-prepend">
                      <div class="input-group-text">Titulo</div>
                    </div>
                    <input required name="title" type="text" class="form-control" value="' . $title . '" id="inlineFormInputGroup" placeholder="Palabras clave">
                </div>
              </div>
              <div class="col-auto">
                <label class="sr-only" for="inlineFormInputGroup">Username</label>
                <div class="input-group mb-2">
                  <div class="input-group-prepend">
                    <div class="input-group-text">Año</div>
                  </div>
                  <input name="year" type="number" class="form-control" id="inlineFormInputGroup" placeholder="Ingrese el año">
                </div>
              </div>
              <div class="col-auto">
              <label class="sr-only" for="inlineFormInputGroup">Director</label>
              <div class="input-group mb-2">
                <div class="input-group-prepend">
                  <div class="input-group-text">Director</div>
                </div>
                <input name="director" type="text" class="form-control" id="inlineFormInputGroup" placeholder="Especifique el director">
              </div>
              </div>
              <div class="col-auto">
                <button type="submit" class="btn btn-warning mb-2">Buscar</button>
              </div>
            </div>
          </form>';
  }

  public function haveReview($m, $u)
  {
    $temp = new Connection();
    $conn = $temp->getConnection();
    if ($temp->getCount($conn, "SELECT COUNT(*) FROM review WHERE pelicula = '$m' AND usuario = '$u'") == 0) {
      return "Escriba una reseña";
    } else {
      return "Edite su reseña";
    }
  }
}
