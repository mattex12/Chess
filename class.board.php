<?php
require_once "class.player.php";
		
		
class OnlineChess_Board {
	
	public $square;   // (array) Tableau d'objets OnlineChess_Square. Represente le plateau avec les pieces
	
	public $white;    // (object) Joueur blanc (type: OnlineChess_Player)
	public $black;    // (object) Joueur noirr (type: OnlineChess_Player)
	public $user;     // (object) Depend du tour du joueur. Alias de white et black
	public $opponent; // (object) Depend du tour du joueur opposant.  Alias de white et black
	
	public $history;       // (array) Tableau ou on stocke tous les mouvements de la partie
	public $removed_piece; // (object) La derniere piece retiree du plateau, ou FALSE si la case est vide
	
	public $has_moved;     // (bool) "true" si le joueur a deplace une piece, "false" sinon.
	public $is_check;      // (bool) "true" si le Roi du joueur est attaque
	public $is_check_mate; // (bool) "true" si le joueur capture le Roi oppose
	public $is_stalemate;  // (bool) "true" si le joueur ne peut plus faire de mouvement legal
	public $dbh;		   // Connexion a la base de dnnees
	
	/*
	 *  @access public
	 *
	 *  Contructor.
	 */
	public function __construct() {
		// Le constructeur va initialiser le plateau de jeu
		// Le tableau $this->square represente le plateau de jeu
		// Il contient les instances de  OnlineChess_Square qui represente les cases
		$this->square = array();
		// On instancie les deux joueurs (classe OnlineChess_Player)
		$this->white    =  new OnlineChess_Player("white", $_SESSION['white']);
		$this->black    =  new OnlineChess_Player("black", $_SESSION['black']);
		// En debut de partie, le joueur blanc commence
		$this->user     = $this->white;
		$this->opponent = $this->black;
		// history est un tableau vide vide
		$this->history       = array();
		// On initialise a FALSE les autres proprietes
		$this->removed_piece = false;
		$this->has_moved     = false;
		$this->is_check      = false;
		$this->is_check_mate = false;
		$this->is_draw       = false;
		// On se connecte a la base
		$this->dbh = new DataBase();
		
		// Si pas de partie ($_SESSION['idgame'] == 0), on en cree une par la méthode createGame de l'objet board
		if ($_SESSION['idgame'] == 0) {
			$this->createGame($_SESSION['white'], $_SESSION['black']);
		}
		
		
		// Remplit le tableau $this->square avec 8x8 instanciations de OnlineChess_Square
		// Boucle sur les coordonnees x de 1 a 8
		
		for ($x = 1; $x <= 8; $x++) {
			for ($y = 1; $y <= 8; $y++) {
				$this->square[$x][$y] = new OnlineChess_Square($x, $y);
			}
		}
		
		// On ajoute les pieces au plateau(dans la position de depart) en utilisant les methode locale
		// Ajout d'une tour noire en position 1, 8
		// Ajout un cavalier noir en position 2, 8
		// Ajout un fou noir en position 3, 8
		// ...
		// Ajout une tour blanche en position 1, 1
		//etc.
		$this->addRook("black", 1, 8);
		$this->addKnight("black", 2, 8);
		$this->addBishop("black", 3, 8);
		$this->addQueen("black", 4, 8);
		$this->addKing("black", 5, 8);
		$this->addBishop("black", 6, 8);
		$this->addKnight("black", 7, 8);
		$this->addRook("black", 8, 8);
		
		$this->addRook("white", 1, 1);
		$this->addKnight("white", 2, 1);
		$this->addBishop("white", 3, 1);
		$this->addQueen("white", 4, 1);
		$this->addKing("white", 5, 1);
		$this->addBishop("white", 6, 1);
		$this->addKnight("white", 7, 1);
		$this->addRook("white", 8, 1);
		
		for ($i = 1; $i <= 8; $i++) {
			$this->addPawn("black", $i, 7);
			$this->addPawn("white", $i, 2);
		}
		
		// on recupere les mouvements de la partie par la methode unpackData
		$this->unpackData();
		//  Mise a jour des cases controllées par l'adversaire
		$this->updateData();
	}
	
	/*
	 * Creation d'une nouvelle partie
	 * @param white : joue les blancs
	 * @param white : joue les noirs
	 *
	 */
	public function createGame(string $white, string $black) {		
		// Oninsere dans la table 'game' la nouvelle partie avec les identifiants black and white
		$sql = "INSERT INTO game (white, black) VALUES (:white, :black)";
		
		$query = $this->dbh->prepare($sql);
		$query->bindParam(':white', $white, PDO::PARAM_INT);
		$query->bindParam(':black', $black, PDO::PARAM_INT);
		$query->execute();
	}
	
	/*
	 * @return la couleur de joueur dont c'est le tour
	 * Si le nombre de lignes dans $this->history est pair alors c'est aux blancs de jouer, au noirs sinon
	 * 
	 * Si le parametre est a false
	 * @return la couleur de l'opposant
	 */
	public function turn(bool $this_user = true) :string {
		if ($this_user === true) {
			if (count($this->history) % 2 === 0) {
				return 'white';
			} else {
				return 'black';
			}
		} else {
			if (count($this->history) % 2 === 1) {
				return 'white';
			} else {
				return 'black';
			}
		}
	}
	
	
	/*
	 * Realise le mouvement d'une piece de $x_start, $y_start vers $x_end, $y_end
	 *
	 */
	public function movePiece($x_start, $y_start, $x_end, $y_end, $really_move = true) :bool {
		// Si la case est vide, on retourne FALSE
		if (FALSE === $this->square[$x_start][$y_start]->hasPiece()) {
			return false;
		}
		
		
		// On recupere les deux pieces et on les enleve du plateau
		$piece_mov = $this->removePiece($x_start, $y_start);
		$piece_cap = $this->removePiece($x_end, $y_end);
		
		
		// Verifie la prise "en passant"
		if ($piece_mov->isPawn()  &&  (abs($x_start - $x_end) == 1)  &&  !$piece_cap) {
			$piece_cap = $this->removePiece($x_end, $y_start);
		}
		
		
		// Place la piece sur la case cible
		$this->square[$x_end][$y_end]->piece = $piece_mov;
		
		
		// Mise a jour des cases controllées par l'adversaire
		$this->updateData();
		
		// verifier que le Roi n'est pas sous le controle d'une piece adverse
		if (!$piece_mov->isKing()) {
			$king   = $this->{$piece_mov->color}->pieces['king'];
			$king_square = $this->square[$king->x][$king->y];
		} else {
			$king_square = $this->square[$x_end][$y_end];
		}
		
		foreach ($king_square->controlled_by as $p) {
			if ($p->color != $piece_mov->color) {
				$this->removePiece($x_end, $y_end);
				
				$this->square[$piece_mov->x][$piece_mov->y]->piece = $piece_mov;
				
				if ($piece_cap) {
					$this->square[$piece_cap->x][$piece_cap->y]->piece = $piece_cap;
				}
				return false;
			}
		}
		
		
		// If was only checking
		if (!$really_move)
		{
			$this->removePiece($x_end, $y_end);
			$this->square[$piece_mov->x][$piece_mov->y]->piece = $piece_mov;
			
			if ($piece_cap) {
				$this->square[$piece_cap->x][$piece_cap->y]->piece = $piece_cap;
			}
			
			return true;
		}
		
		$this->removed_piece = $piece_cap;
		
		$piece_mov->x = $x_end;
		$piece_mov->y = $y_end;
		$piece_mov->num_moves++;
		
		// Test pour le "roque"
		if ($piece_mov->isKing()  &&  (abs($x_start - $x_end) == 2)) {
			if ($x_start > $x_end) {
				$rook = $this->removePiece(1, $piece_mov->y);
				$this->square[4][$piece_mov->y]->piece = $rook;
				
				$rook->x = 4;
			} else {
				$rook = $this->removePiece(8, $piece_mov->y);
				$this->square[6][$piece_mov->y]->piece = $rook;
				
				$rook->x = 6;
			}
			
			$rook->num_moves++;
		}
		
		// Test pour le pion arrive au bord oppose
		else if ($piece_mov->isPawn()  &&  (($piece_mov->y == 1)  ||  ($piece_mov->y == 8))) {
			$this->addQueen($piece_mov->color, $piece_mov->x, $piece_mov->y, $piece_mov->num_moves);
		}
		
		// Mise a jour du tableau $this->history
		$this->history[] = $x_start . $y_start . $x_end . $y_end;
		
		return true;
	}
	
	
	/*
	 * Retire une piece du plateau
	 * @return la piece retiree
	 */
	public function removePiece(int $x, int $y) {
		// On recupere la piece a retirer a partir de l'objet $this->square[$x][$y]
		$removed_piece = $this->square[$x][$y]->piece;
		
		// On remplace la piece par false
		$this->square[$x][$y]->piece = false;
		
		// On retourne la piece
		return $removed_piece;
	}
	
	
	/*
	 * Verifie si le Roi est en echec
	 *
	 */
	public function verifyCheckMate() : bool {
		$this->is_check      = false;
		$this->is_check_mate = false;
		$this->is_draw       = false;
		
		$player = $this->{$this->turn()};
		
		$king        = $player->pieces['king'];
		$king_square = $this->square[$king->x][$king->y];
		
		$this->updateData();
		
		foreach ($king_square->controlled_by as $piece) {
			if ($piece->color != $king->color) {
				$this->is_check = true;
				break;
			}
		}
		
		foreach ($king->controlled_squares as $move) {
			if ($king->validateMove($this, $move[0], $move[1])) {
				if ($this->movePiece($king->x, $king->y, $move[0], $move[1], false)) {
					return false;
				}
			}
		}
		
		
		
		for ($x = 1; $x <= 8; $x++) {
			for ($y = 1; $y <= 8; $y++) {
				$square = $this->square[$x][$y];
				$piece  = $square->piece;
				
				if (!$square->hasPiece()  ||  ($piece->color != $king->color)  ||  $piece->isKing()) {
					continue;
				}
				
				foreach ($piece->controlled_squares as $move) {
					$x_start = $piece->x;
					$y_start = $piece->y;
					$x_end   = $move[0];
					$y_end   = $move[1];
					
					if ($this->movePiece($x_start, $y_start, $x_end, $y_end, false)) {
						return false;
					}
				}
			}
		}
		
		
		if ($this->is_check) {
			$this->is_check_mate = true;
			return true;
		} else {
			$this->is_draw = true;
			return false;
		}
	}
	
	/*
	 * Ajoute un roi 
	 * àparam $color
	 * @param $x position x
	 * @param $y position y
	 * @param $num_moves le nombre de mouvement que la piece a effectue
	 * 
	 * Rempli le tableau $this->square avec les objets pieces correspondants
	 * On cree une instance de la classe correspondante a la piece (OnlineChess_King, etc...)
	 * On passe l'objet a la methode addPiece (de Online_Player)
	 */
	private function addKing($color, $x, $y) {
		$this->square[$x][$y]->piece = $this->$color->addPiece(new OnlineChess_King($color, $x, $y, 0));
	}
	
	private function addQueen($color, $x, $y) {
		$this->square[$x][$y]->piece = $this->$color->addPiece(new OnlineChess_Queen($color, $x, $y, 0));
	}
	
	private function addBishop($color, $x, $y) {
		$this->square[$x][$y]->piece = $this->$color->addPiece(new OnlineChess_Bishop($color, $x, $y, 0));
	}
	
	private function addKnight($color, $x, $y) {
		$this->square[$x][$y]->piece = $this->$color->addPiece(new OnlineChess_Knight($color, $x, $y, 0));
	}
	
	private function addRook($color, $x, $y) {
		$this->square[$x][$y]->piece = $this->$color->addPiece(new OnlineChess_Rook($color, $x, $y, 0));
	}
	
	private function addPawn($color, $x, $y) {
		$this->square[$x][$y]->piece = $this->$color->addPiece(new OnlineChess_Pawn($color, $x, $y, 0));
	}
	
	
	
	
	// Met a jour toutes les donnees concernat les cases controllees par l'opposant
	public function updateData() {
		$opponent_color = $this->turn(false);
		
		for ($x = 1; $x <= 8; $x++) {
			for ($y = 1; $y <= 8; $y++) {
				$this->square[$x][$y]->controlled_by = array();
			}
		}
		
		for ($x = 1; $x <= 8; $x++) {
			for ($y = 1; $y <= 8; $y++) {
				$square = $this->square[$x][$y];
				
				if ($square->hasPiece()  &&  ($square->piece->color == $opponent_color)) {
					$square->piece->getControlledSquares($this);
				}
			}
		}
	}
	
	/*
	 *  Rejoue la partie en se basant sur l'historique en base de donnees
	 *
	 */
	public function unpackData() {
		$gameId = $_SESSION['idgame'];
		
		$sql = "SELECT history FROM gamedata WHERE game=:game ORDER BY id ASC";
		$query = $this->dbh->prepare($sql);
		$query->bindParam(':game', $gameId, PDO::PARAM_INT);
		$query->execute();
		$results = $query->fetchAll(PDO::FETCH_ASSOC);
		
		if (is_array($results)) {
			foreach ($results AS $data) {
				$move = $data['history'];
				
				if (!preg_match("/^[1-8]{4}$/", $move)) {
					continue;
				}
				$this->movePiece($move[0], $move[1], $move[2], $move[3]);
			}
			
			if (count($results) % 2 == 0) {
				$this->user    = $this->white;
				$this->opponent = $this->black;
			} else {
				$this->user    = $this->black;
				$this->opponent = $this->white;
			}
			
			$this->verifyCheckMate();
		}
		return true;
	}
}