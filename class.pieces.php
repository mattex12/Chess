<?php
class OnlineChess_Piece
{
	public $color;              // (string)
	public $name;               // (string)
	public $x;                  // (int)
	public $y;                  // (int)
	public $num_moves;          // (int) Nombre de mouvement depuis le debut de la partie
	public $controlled_squares; // (array) cases que cette piece controle
	
	public function __construct($color, $x, $y) {
		$this->color              = (string)$color;
		$this->name               = "";
		$this->x                  = (int)$x;
		$this->y                  = (int)$y;
		$this->num_moves          = 0;
		$this->controlled_squares = array();
	}
	
	/*
	 * Renvoie le nom de la piece a partir du nom de la classe de la piece
	 */
	public function getName(bool $translate = false) :string {
		$tmp = strtolower(preg_replace("/^OnlineChess_/i", "", get_class($this)));
		
		if ($translate == true) {
		switch ($tmp) {
			case 'king':
				$name = 'Le Roi';
				break;
			case 'queen' :
				$name = 'La Reine';
				break;
			case 'rook' :
				$name = 'La tour';
				break;
			case 'knight':
				$name = 'Le cavalier';
				break;
			case 'bishop' :
				$name = 'Le fou';
				break;
			case 'pawn' :
				$name = 'Le pion';
				break;
		}
		
		return $name;
		} else {
			return $tmp;
		}
	}
	
	/*
	 * Pour les fonctions suivantes, on a besoin de savoir si la piece
	 * est un Roi, une tour ou un pion
	 */
	
	public function isKing() :bool {
		return preg_match("/king/i", get_class($this));
	}
	
	public function isRook() {
		return preg_match("/rook/i", get_class($this));
	}
	
	public function isPawn() {
		return preg_match("/pawn/i", get_class($this));
	}
}





class OnlineChess_King extends OnlineChess_Piece {
	
	// Le constructeur herite du parent la couleur et la position
	public function __construct($color, $x, $y) {
		parent::__construct($color, $x, $y);
	}
	
	// Les cases controlees par cette piece sont les cases sur lesquelles cette piece peut se rendre au prochain tour
	// Elles peuvent etre vides ou occupees par une piece adverse
	// On compte aussi les cases occupees par une piece du joueur, car si celle-ci est capturee au prochain tour
	// alors la case sera controllee par le joueur.
	
	/* fonction  validateMove(&$board, $x, $y, $piece_turn)
	 * La fonction valide le mouvement du joueur
	 * @param board l'objet plateau
	 * @param $x, $y les coordonnees de la case de destination
	 * @piece_turn bool
	 *
	 * @return bool
	 */
	public function validateMove($board, $x, $y, $piece_turn = true) :bool {
		// si le mouvement est en dehord du plateau on retourne FALSE
		if (($x < 1)  ||  ($x > 8)  ||  ($y < 1)  ||  ($y > 8)) {
			return false;
		}
		
		$x_variation = abs($this->x - $x);
		$y_variation = abs($this->y - $y);
		
		
		if ($x_variation == 0) {
			if ($y_variation != 1) {
				return false;
			}
		} else if ($x_variation == 1) {
			if (($y_variation != 0)  &&  ($y_variation != 1)) {
				return false;
			}
		}
		
		// Castling
		else if (($x_variation == 2)  &&  ($this->num_moves == 0)){
			if ($y_variation != 0)
			{
				return false;
			}
			
			
			if ($x < $this->x)
			{
				// If King walked to "A" file, checks if the square beside the Rook is vacant
				if ($board->square[2][$this->y]->hasPiece())
				{
					return false;
				}
				
				$rook = $board->square[1][$this->y]->piece;
				$_x = -1;
			}
			else
			{
				$rook = $board->square[8][$this->y]->piece;
				$_x = +1;
			}
			
			// Checks if rook has not moved yet
			if ($rook  &&  $rook->isRook()  &&  ($rook->num_moves == 0))
			{
				// Checks if king is not under attack
				foreach ($board->square[$this->x][$this->y]->controlled_by as $p)
				{
					if ($p->color != $this->color)
					{
						return false;
					}
				}
				
				// Checks if all of the squares between the king and rook are vacant
				// Checks if king is not passing through or arriving upon a square controlled by the opponent
				for ($i = 1; $i <= 2; $i++)
				{
					$square = $board->square[  ($this->x + ($i * $_x))  ][$this->y];
					
					if ($square->hasPiece())
					{
						return false;
					}
					
					foreach ($square->controlled_by as $p)
					{
						if ($p->color != $this->color)
						{
							return false;
						}
					}
				}
				
				return true;
			}
			
			return false;
		}
		
		else
		{
			// $x_variation is not 0, 1 or 2
			return false;
		}
		
		
		$square = $board->square[$x][$y];
		
		if (($this->color != $board->turn())  ||  (count($square->controlled_by) == 0))
		{
			if (!$square->hasPiece()  ||  ($square->piece->color != $this->color)  ||  !$piece_turn)
			{
				return true;
			}
		}
		
		return false;
	}
	
	
	/*
	 * @param $board une instance du plateau
	 * @return un tableau des cases controlées par la pièce
	 */
	public function getControlledSquares($board) :array {
		$this->controlled_squares = array();
		
		$piece_turn = ($this->color == $board->turn());
		
		
		for ($x = ($this->x - 1); $x <= ($this->x + 1); $x++)
		{
			for ($y = ($this->y - 1); $y <= ($this->y + 1); $y++)
			{
				if ($this->validateMove($board, $x, $y, $piece_turn))
				{
					
					$this->controlled_squares[] = $x . $y;
					
					if (!$piece_turn)
					{
						$board->square[$x][$y]->controlled_by[] = $this;
					}
				}
			}
		}
		
		
		if ($this->num_moves == 0) {
			for ($x = ($this->x - 2); $x <= ($this->x + 2); $x += 4) {
				if ($this->validateMove($board, $x, $this->y, $piece_turn)) {
					$this->controlled_squares[] = $x . $this->y;
				}
			}
		}
		return $this->controlled_squares;
	}
}





class OnlineChess_Queen extends OnlineChess_Piece{
	function __construct($color, $x, $y) {
		parent::__construct($color, $x, $y);
	}
	
	
	
	public function validateMove($board, $x, $y, $piece_turn = true) {
		if (($x < 1)  ||  ($x > 8)  ||  ($y < 1)  ||  ($y > 8)) {
			return false;
		}
		
		$x_variation = abs($this->x - $x);
		$y_variation = abs($this->y - $y);
		
		
		if (($x_variation == 0)  &&  ($y_variation == 0)) {
			return false;
		} else if (($x_variation != 0)  &&  ($y_variation != 0)) {
			if ($x_variation != $y_variation) {
				return false;
			}
		}
		
		$_x = ($x < $this->x)  ?  -1  :  (($x > $this->x)  ?  +1  :  0);
		$_y = ($y < $this->y)  ?  -1  :  (($y > $this->y)  ?  +1  :  0);
		
		$i = $this->x + $_x;
		$j = $this->y + $_y;
		
		while (($i != $x)  ||  ($j != $y))
		{
			if ($board->square[$i][$j]->hasPiece())
			{
				return false;
			}
			
			$i += $_x;
			$j += $_y;
		}
		
		$square = $board->square[$x][$y];
		
		if (!$square->hasPiece()  ||  ($square->piece->color != $this->color)  ||  !$piece_turn) {
			return true;
		}
		
		return false;
	}
	
	
	
	function getControlledSquares($board) {
		$this->controlled_squares = array();
		
		$piece_turn = ($this->color == $board->turn());
		
		
		$x_directions = $y_directions = array(-1, 0, 1);
		
		foreach ($x_directions as $_x)
		{
			foreach ($y_directions as $_y)
			{
				if (($_x == 0)  &&  ($_y == 0))
				{
					continue;
				}
				
				$x = $this->x + $_x;
				$y = $this->y + $_y;
				
				while ($this->validateMove($board, $x, $y, $piece_turn))
				{
					
					$this->controlled_squares[] = $x . $y;
					
					if (!$piece_turn)
					{
						$board->square[$x][$y]->controlled_by[] = $this;
					}
					
					
					$x += $_x;
					$y += $_y;
				}
			}
		}
		
		return $this->controlled_squares;
	}
}





class OnlineChess_Bishop extends OnlineChess_Piece{
	function __construct($color, $x, $y){
		parent::__construct($color, $x, $y);
	}
	
	
	
	function validateMove($board, $x, $y, $piece_turn = true){
		if (($x < 1)  ||  ($x > 8)  ||  ($y < 1)  ||  ($y > 8))
		{
			return false;
		}
		
		$x_variation = abs($this->x - $x);
		$y_variation = abs($this->y - $y);
		
		
		if (($x_variation == 0)  ||  ($y_variation == 0)  ||  ($x_variation != $y_variation))
		{
			return false;
		}
		
		$_x = ($x < $this->x)  ?  -1  :  +1;
		$_y = ($y < $this->y)  ?  -1  :  +1;
		
		$i = $this->x + $_x;
		$j = $this->y + $_y;
		
		while (($i != $x)  ||  ($j != $y))
		{
			if ($board->square[$i][$j]->hasPiece())
			{
				return false;
			}
			
			$i += $_x;
			$j += $_y;
		}
		
		$square = $board->square[$x][$y];
		
		if (!$square->hasPiece()  ||  ($square->piece->color != $this->color)  ||  !$piece_turn)
		{
			return true;
		}
		
		return false;
	}
	
	
	
	function getControlledSquares($board){
		$this->controlled_squares = array();
		
		$piece_turn = ($this->color == $board->turn());
		
		
		$x_directions = $y_directions = array(-1, 1);
		
		foreach ($x_directions as $_x)
		{
			foreach ($y_directions as $_y)
			{
				$x = $this->x + $_x;
				$y = $this->y + $_y;
				
				while ($this->validateMove($board, $x, $y, $piece_turn))
				{
					
					$this->controlled_squares[] = $x . $y;
					
					if (!$piece_turn)
					{
						$board->square[$x][$y]->controlled_by[] = $this;
					}
					
					
					$x += $_x;
					$y += $_y;
				}
			}
		}
		
		return $this->controlled_squares;
	}
}





class OnlineChess_Knight extends OnlineChess_Piece{
	function __construct($color, $x, $y){
		parent::__construct($color, $x, $y);
	}
	
	
	
	function validateMove($board, $x, $y, $piece_turn = true)
	{
		if (($x < 1)  ||  ($x > 8)  ||  ($y < 1)  ||  ($y > 8))
		{
			return false;
		}
		
		$x_variation = abs($this->x - $x);
		$y_variation = abs($this->y - $y);
		
		
		if (  !((($x_variation == 1) && ($y_variation == 2))  ||  (($x_variation == 2) && ($y_variation == 1)))  )
		{
			return false;
		}
		
		
		$square = $board->square[$x][$y];
		
		if (!$square->hasPiece()  ||  ($square->piece->color != $this->color)  ||  !$piece_turn)
		{
			return true;
		}
		
		return false;
	}
	
	
	
	function getControlledSquares($board)
	{
		$this->controlled_squares = array();
		
		$piece_turn = ($this->color == $board->turn());
		
		
		$x_positions = $y_positions = array(-2, -1, 1, 2);
		
		foreach ($x_positions as $_x)
		{
			foreach ($y_positions as $_y)
			{
				if (abs($_x) == abs($_y))
				{
					continue;
				}
				
				$x = $this->x + $_x;
				$y = $this->y + $_y;
				
				if ($this->validateMove($board, $x, $y, $piece_turn))
				{
					
					$this->controlled_squares[] =  $x . $y;
					
					if (!$piece_turn)
					{
						$board->square[$x][$y]->controlled_by[] = $this;
					}
				}
			}
		}
		
		return $this->controlled_squares;
	}
}





class OnlineChess_Rook extends OnlineChess_Piece{
	function __construct($color, $x, $y) {
		parent::__construct($color, $x, $y);
	}
	
	
	
	function validateMove($board, $x, $y, $piece_turn = true) :bool {
		
		if (($x < 1)  ||  ($x > 8)  ||  ($y < 1)  ||  ($y > 8)) {
			return false;
		}
		
		$x_variation = abs($this->x - $x);
		$y_variation = abs($this->y - $y);
		
		
		if (!(($x_variation == 0)  ^  ($y_variation == 0))) {
			return false;
		}
		
		$_x = ($x < $this->x)  ?  -1  :  (($x > $this->x)  ?  +1  :  0);
		$_y = ($y < $this->y)  ?  -1  :  (($y > $this->y)  ?  +1  :  0);
		
		$i = $this->x + $_x;
		$j = $this->y + $_y;
		
		while (($i != $x)  ||  ($j != $y)) {
			if ($board->square[$i][$j]->hasPiece()){
				return false;
			}
			
			$i += $_x;
			$j += $_y;
		}
		
		$square = $board->square[$x][$y];
		
		if (!$square->hasPiece()  ||  ($square->piece->color != $this->color)  ||  !$piece_turn) {
			return true;
		}
		return false;
	}
	
	
	
	function getControlledSquares($board) {
		$this->controlled_squares = array();
		
		$piece_turn = ($this->color == $board->turn());
		
		$x_directions = $y_directions = array(-1, 0, 1);
		
		foreach ($x_directions as $_x) {
			foreach ($y_directions as $_y) {
				if (!(($_x == 0)  ^  ($_y == 0)))
				{
					continue;
				}
				
				$x = $this->x + $_x;
				$y = $this->y + $_y;
				
				while ($this->validateMove($board, $x, $y, $piece_turn))
				{
					
					$this->controlled_squares[] =  $x . $y;
					
					if (!$piece_turn)
					{
						$board->square[$x][$y]->controlled_by[] = $this;
					}
					
					
					$x += $_x;
					$y += $_y;
				}
			}
		}
		
		return $this->controlled_squares;
	}
}





class OnlineChess_Pawn extends OnlineChess_Piece {
	function __construct($color, $x, $y) {
		parent::__construct($color, $x, $y);
	}
	
	
	
	public function validateMove(&$board, $x, $y, $piece_turn = true) :bool {
		if (($x < 1)  ||  ($x > 8)  ||  ($y < 1)  ||  ($y > 8))  {
			return false;
		} else if (($this->y < $y)  &&  ($this->color != "white")  ||  ($this->y > $y)  &&  ($this->color != "black"))  {
			return false;
		}
		
		$x_variation = abs($this->x - $x);
		$y_variation = abs($this->y - $y);
		
		$_y = ($this->color == "white")  ?  +1  :  -1;
		
		
		if ($x_variation == 0)
		{
			$i = $this->x;
			$j = $this->y + $_y;
			
			$square = $board->square[$i][$j];
			
			if (!$square->hasPiece())
			{
				if ($y_variation == 1)
				{
					return true;
				}
				else if (($y_variation == 2)  &&  ($this->num_moves == 0))
				{
					$j += $_y;
					
					$square = $board->square[$i][$j];
					
					if (!$square->hasPiece())
					{
						return true;
					}
				}
			}
		}
		
		else if ($x_variation == 1)
		{
			if ($y_variation == 1)
			{
				$square = $board->square[$x][$y];
				
				if ($square->hasPiece())
				{
					if (($square->piece->color != $this->color)  ||  !$piece_turn)
					{
						return true;
					}
				}
				
				// "En passant" (capture while passing)
				else
				{
					$square = $board->square[$x][$this->y];
					
					if ($square->hasPiece()  &&  $square->piece->isPawn()  &&  ($square->piece->color != $this->color))
					{
						$move =  end($board->history);
						$pawn = $square->piece;
						
						if (($pawn->num_moves == 1)  &&  ($pawn->x == $move[2])  &&  ($pawn->y == $move[3]))
						{
							return true;
						}
					}
				}
			}
		}
		
		return false;
	}
	
	
	
	public function getControlledSquares($board) {
		$this->controlled_squares = array();
		
		$piece_turn = ($this->color == $board->turn());
		
		
		$_y = ($this->color == "white")  ?  +1  :  -1;
		
		for ($x = ($this->x - 1); $x <= ($this->x + 1); $x += 2)
		{
			$y = $this->y;
			
			if ($this->validateMove($board, $x, $y, $piece_turn))
			{
				$this->controlled_squares[] = $x . $y;
				
				if (!$piece_turn)
				{
					$board->square[$x][$y]->controlled_by[] = $this;
				}
			}
			
			$y = $this->y + $_y;
			
			if ($this->validateMove($board, $x, $y, $piece_turn))
			{
				$this->controlled_squares[] = $x . $y;
				
				if (!$piece_turn)
				{
					$board->square[$x][$y]->controlled_by[] = $this;
				}
			}
		}
		
		$x = $this->x;
		$y = $this->y + $_y;
		
		if ($this->validateMove($board, $x, $y, $piece_turn))
		{
			$this->controlled_squares[] = $x . $y;
			
			if ($this->num_moves == 0)
			{
				$y += $_y;
				
				if ($this->validateMove($board, $x, $y, $piece_turn))
				{
					$this->controlled_squares[] = $x . $y;
				}
			}
		}
		
		return $this->controlled_squares;
	}
}
?>