<?php
require_once "database.php";
require_once "class.board.php";
require_once "class.interface.php";
/**
 *  class OnlineChess
 *
 *  Classe principale. Controle toutes les fonctionnalites.
 */
class OnlineChess
{

    public $board;     // (object) Contient toutes les informations des cases et des pieces

    public $interface; // (object) Contient les methodes pour afficher le plateau

    public $dbh;        // handler de connection a la base de donnees

    /*
    *  @access public
    *
    *  Contructor.
    */
    function __construct()
    {
        // On cree l'objet board (instanciation de OnlineChess_Board);
        $this->board     = new OnlineChess_Board();
        // On cree l'objet interface (instanciation de OnlineChess_Interface);
        $this->interface = new OnlineChess_Interface($this->board);
        // On se connecte a la base de donnee
        $this->dbh       = new DataBase();

        // On recupere les coordonnees du mouvement du joueur dans le tableau $_POST['mov_start'], $_POST['mov_end']
        if (isset($_POST['mov_start'], $_POST['mov_end'])) {
            if (preg_match("/^[A-H][1-8]$/i", $_POST['mov_start'])  &&  preg_match("/^[A-H][1-8]$/i", $_POST['mov_end'])) {
                // On recupere les coodonnees de depart et d'arrivee de la piece,
                //$_POST['mov_start'][0], $_POST['mov_start'][1], etc
                $x_start = $this->interface->valueOfX($_POST['mov_start'][0]);
                $y_start = $_POST['mov_start'][1];
                $x_end   = $this->interface->valueOfX($_POST['mov_end'][0]);
                $y_end   = $_POST['mov_end'][1];
                // On confirme le mouvement par la methode $this->confirmMove.
                $this->confirmMove($x_start, $y_start, $x_end, $y_end);
            } else {
                $this->interface->error("Invalid move: <strong>" . $_POST['mov_start'] . $_POST['mov_end'] . "</strong>");
            }
        }
    }

    /*
    *  @function setImageDir( string $dir )
    *
    *  @access public
    *  
    *  Definit un repertoire pour les images des pieces. Il doit etre valide.
    *  Le repertoire est stocke dans la propriete image_dir de l'objet OnlineChess_Interface 
    *  Le nom des images doit etre de la forme :
    *  "white_king.png", "black_king.png", "white_queen.png" etc.
    *  Noms des pieces "king", "queen", "bishop", "knight", "rook" and "pawn".
    */
    public function setImageDir(string $dir): bool
    {
        $dir = (string)$dir;

        if (!is_dir($dir)) {
            $this->interface->error("Le dossier images n'existe pas.");
            return false;
        }

        $this->interface->image_dir = $dir;

        return true;
    }


    /*
    *  @function board( [ bool $return ] )
    *
    *  @access public
    *
    *  Retourne le code html du plateau complet. 
    *  Si on veut que le code soit sous la forme d'une chaine, mettre le parametre a TRUE,
    */
    public function board(): string
    {
        return $this->interface->board();
    }



    /*
    *  @function form( [ bool $return ] )
    *
    *  @access public
    *
    *  Retourne le code HTML du formulaire de mouvement
    *  Si on veut que le code soit sous la forme d'une chaine, mettre le parametre a TRUE,
    */
    public function form(): string
    {
        return $this->interface->form();
    }



    /*
    *  @function message( [ bool $return ] )
    *
    *  @access public
    *
    *  Retourne un message
    */
    public function message(bool $bool_return = false): string
    {
        return $this->interface->message($bool_return);
    }


    /*
    *  @function confirmMove( int $x_start, int $y_start, int $x_end, int $y_end )
    *
    *  @access private
    *
    *  Appelee dans le constructeur si le joueur a deplace une piece
    */
    private function confirmMove($x_start, $y_start, $x_end, $y_end): bool
    {

        // On recupere la case de depart
        $square = $this->board->square[$x_start][$y_start];
        // Et la piece qui est dessus
        $piece  = $square->piece;


        if (!$square->hasPiece() || ($piece->color != $this->board->turn())) {
            $this->interface->setMessage("Please move a <strong>" . $this->board->turn() . "</strong> piece.");
            return false;
        } else if (!$piece->validateMove($this->board, $x_end, $y_end)) {
            $move = $this->interface->valueOfX($x_start) . $y_start . $this->interface->valueOfX($x_end) . $y_end;
            $this->interface->setMessage("<strong>Notice!</strong> " . $move . " is not a valid move.");
            return false;
        } else if (!$this->board->movePiece($x_start, $y_start, $x_end, $y_end)) {
            $this->interface->setMessage("<strong>Attention!</strong> Votre Roi ne peut se deplacer sur une case controlee par votre adversaire.");
            return false;
        }

        $this->board->has_moved = true;

        // TODO On recupre l'id de partie dans le tableau $_SESSION
        $gameId = $_SESSION['idgame'];
        $history = (string) $x_start . $y_start . $x_end . $y_end;

        // On ins�re le mouvement dans la base
        $sql = "INSERT INTO gamedata (game, history) VALUES (:game, :history)";
        $query = $this->dbh->prepare($sql);
        $query->bindParam(':game', $gameId, PDO::PARAM_INT);
        $query->bindParam(':history', $history, PDO::PARAM_STR);
        $query->execute();

        // On verifie si il y a echec
        $this->board->verifyCheckMate();

        return true;
    }

    /*
     *  @function getPlayer()
     *
     *  @access public
     *
     *  @return le nom du joueur dont c'est le tour
     */
    public function getPlayer(): string
    {
        // TODO On recupere le nombre de mouvements dans la table gamedata
        $sql = "SELECT COUNT(*) FROM gamedata WHERE game=" . $_SESSION['idgame'];
        $query = $this->dbh->prepare($sql);
        $query->execute();
        $res = $query->fetch(PDO::FETCH_ASSOC);

        $nbMove = $res['COUNT(*)'];
        // Si le nombre est pair, on renvoie le login du joueur blanc, noir sinon.
        if ($nbMove % 2 === 0) {
            return $_SESSION['white'];
        } else {
            return $_SESSION['black'];
        }
    }

    /*
    *   @function getTheme()
    *
    *   return le theme du joueur passé en paramètre
    *
    */
    public function getTheme(int $idPlayer): string
    {
        $sql = "SELECT theme from player WHERE id = :id";
        $query = $this->dbh->prepare($sql);
        $query->bindParam(':id', $idPlayer, PDO::PARAM_INT);

        $query->execute();
        $res = $query->fetch(PDO::FETCH_OBJ);

        return $res->theme;
    }
}
