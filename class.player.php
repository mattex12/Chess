<?php
class OnlineChess_Player {
	public $color;  // (string) couleur du joueur
	public $pieces; // (array) Tableau d' objets. Chaque element du tableau est une piece possedee par le joueur
	public $player;	// (string) le login
	
	public function __construct($color, $player) {
		$this->color  = (string) $color;
		$this->pieces = array();
		$this->player = (string) $player;
	}
	
	
	/*
	 * Ajoute une piece au plateau dans le tableau $this->square->piece
	 * L'index  du tableau pour le Roi est $this->pieces['king']
	 * L'index est numerique pour les autres pieces
	 * */

	public function addPiece($piece) {
		if ($piece->isKing()) {
			return $this->pieces['king'] = $piece;
		} else {
			return $this->pieces[] = $piece;
		}
	}
}
?>