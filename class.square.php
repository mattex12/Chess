<?php

// Cette classe représente les case du plateau
class OnlineChess_Square {
	var $x;             // (int)   coordonnee x de la case
	var $y;             // (int)   coordonnee y de la case
	var $piece;         // (mixed) "false" si la case est vide, sinon renvoie l'objet piece instance de OnlineChess_piece
	var $controlled_by; // (array) tableau des pieces de l'adversaire qui contrloent la case
	
	function __construct($x, $y) {
		$this->x             = (int)$x;
		$this->y             = (int)$y;
		$this->piece         = false;
		$this->controlled_by = array();
	}
	
	// Cette methode est un getter qui renvoie la propriete $this->piece
	public function hasPiece() :bool  {
		return (bool)$this->piece;
	}
}

?>