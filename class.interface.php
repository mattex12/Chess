<?php
require_once "class.pieces.php";
require_once "class.board.php";
require_once "class.square.php";

class OnlineChess_Interface {
	public $board;             // (object) An alias to "OnlineChess::board" property
	
	public $script_file;       // (string)
	public $javascript_loaded; // (bool)
	public $message;           // (string)
	
	public $image_dir;         // (string)
	public $square_size;       // (int)
	public $show_coordinates;  // (bool)
	
	
	function __construct( OnlineChess_Board $board) {
		$this->board             = $board;
		$this->javascript_loaded = false;
		$this->message           = "";
		$this->image_dir         = "";
		$this->square_size       = 50;
		$this->show_coordinates  = true;
	}
	
	/*
	 * Retourne le code HTML du plateau
	 * Les joueurs voit leur cote en bas du plateau
	 */
	public function board() {
		// Si le joueur joue les blancs, on parcourt le plateau depuis la case en haut à gauche
		// Si le joueur joue les noirs, on parcourt le plateau depuis la case en bas a droite
		if ($_SESSION['login'] == $_SESSION['white']) {
			$x_start     = 1;
			$x_increment = +1;
			
			$y_start     = 8;
			$y_increment = -1;
		} else {
			$x_start     = 8;
			$x_increment = -1;
			
			$y_start     = 1;
			$y_increment = +1;
		}
		
		$table_style  = "margin: 0px auto; text-align: center; cursor: default; ";
		$table_style .= " border: " . floor($this->square_size / 7) . "px double purple;";
		
		$html_board = "\n".'<table cellspacing="0" cellpadding="0" style="' . $table_style . '" id="board">';

		// Boucle sur les y
		for ($y = $y_start; (($y-1 & 8) == 0); $y += $y_increment) {
			// Une nouvelle ligne commence
			$html_board .= "\n".'    <tr>';
			// Boucle sur les x
			for ($x = $x_start; (($x-1 & 8) == 0); $x += $x_increment) {
				// La premiere case est claire
				// On alterne les couleurs en fonction de la parite des x et y
				if (($x % 2) == 0 ) {
					if (($y % 2) == 0 ) {
						$class = "dark";
						$color = "darkgrey";
					} else {
						$class = "light";
						$color = "lightgrey";
					}
				} else {
					if (($y % 2) == 0 ) {
						$class = "light";
						$color = "lightgrey";
					} else {
						$class = "dark";
						$color = "darkgrey";
					}
				}
				
				// On recupere la case du plateau depuis l'objet $this->board
				$square = $this->board->square[$x][$y];
				// Et la piece
				$piece  = $square->piece;
				
				$events  = ' onmouseover="highlightSquare('.$x.', '.$y.', true);"';
				$events .= ' onmouseout="highlightSquare('.$x.', '.$y.', false);"';
				$events .= ' onclick="selectSquare('.$x.', '.$y.');"';
				
				// On stocke le code html dans une variable php
				// Chaque cellule a une largeur et une hauteur de $this->square_size
				// Chaque cellule a pour id="square_' . ($x.$y)
				// La propriete background-color a la val
				// Si il y a une piece sur la case, 
				if ($square->hasPiece()) {
					// On l'affiche au moyen de la methode  $this->printPiece
					$square_contents = $this->printPiece($piece);
				} else {
					// Sinon on affiche rien
					$square_contents = "";
				}
				
				$html_board .= "\n".'        <td id="square_' . ($x.$y) . '"' . $events;
				$html_board .= ' style="width: ' . $this->square_size . 'px; height: ' . $this->square_size . 'px;';
				$html_board .= ' vertical-align: middle; border: 1px solid ' . $color . '; background-color: ' . $color.';"';
				$html_board .= ' class="' . $class . '">';
				$html_board .= "\n".'            ' . $square_contents;
				$html_board .= "\n".'        </td>';
			}
			
			$html_board .= "\n".'    </tr>';
		}
		
		$html_board .= "\n".'</table>';
		
		$html_board .= "\n\n".'<script type="text/javascript">';
		
		if (!empty($_POST['mov_start'])) {
			$html_board .= "\n    selectSquare(".$this->valueOfX($_POST['mov_start'][0]).", ".$_POST['mov_start'][1].");";
		}
		
		if (!empty($_POST['mov_end'])) {
			$html_board .= "\n    selectSquare(".$this->valueOfX($_POST['mov_end'][0]).", ".$_POST['mov_end'][1].");";
		}
		
		$html_board .= "\n</script>\n\n";
		
		$html = $this->loadJavaScript();
		
		if (!$this->show_coordinates) {
			return $this->printHTML(($html . $html_board), $bool_return);
		}
		
		// Outer table (with board coordinates)
		$html .= "\n".'<table style="margin: 0px auto; text-align: center; font-family: Tahoma, Arial;';
		$html .=      ' font-size: 11px; font-weight: bold; color: #777;"';
		$html .=      ' cellspacing="0" cellpadding="0">';
		
		for ($y = $y_start; (($y-1 & 8) == 0); $y += $y_increment) {
			$html .= "\n".'    <tr><td style="height: ' . $this->square_size . 'px;';
			$html .= ' padding-right: 7px; vertical-align: middle;">' . $y . '</td>';
			
			if ($y == $y_start) {
				$html .= '<td colspan="8" rowspan="8">' . $html_board . '</td>';
			}
			
			$html .= '</tr>';
		}
		
		$html .= "\n".'    <tr><td>&nbsp;</td>';
		
		for ($x = $x_start; (($x-1 & 8) == 0); $x += $x_increment) {
			$html .= '<td style="width: ' . $this->square_size . 'px; padding-top: 2px;">' . $this->valueOfX($x) . '</td>';
		}
		
		$html .= "\n".'    </tr>';
		$html .= "\n".'</table>';
		
		return $this->printHTML($html, false);
	}
	
	
	/*
	 * Calcule et affiche le code HTML du formulaire de soumission du mouvement du joueur
	 */
	function form() {
		$disabled = "";
		$html = "";
		
		if ($this->board->has_moved) {
			$disabled = ' disabled="disabled"';
		}

		$html .= "\n".'<form method="post" id="OnlineChess_form style="margin: 20px 0px;"">';
		
		$html .= "\n".'  <input type="hidden" name="mov_start" id="mov_start"';
		$html .=                 ' value="' . (!empty($_POST['mov_start'])  ?  $_POST['mov_start']  :  "") . '" />';
		$html .= "\n".'  <input type="hidden" name="mov_end" id="mov_end"';
		$html .=                 ' value="' . (!empty($_POST['mov_end'])  ?  $_POST['mov_end']  :  "") . '" />';
		
		$html .= "\n".'  <table border="0" id="form_table" cellspacing="0" cellpadding="3" style="margin: 0px auto;">';
		$html .= "\n".'    <tr>';
		$html .= "\n".'      <td style="text-align: center;">';
		$html .= "\n".'        <input type="submit" id="submit_button" value="Move"' . $disabled;
		$html .=                 ' style="margin-bottom: 20px; width: 250px; height: 30px;" />';
		$html .= "\n".'      </td>';
		$html .= "\n".'    </tr>';
		$html .= "\n".'  </table>';
		$html .= "\n".'</form>';
		
		return $this->printHTML($html, false);
	}
	
	/*
	 * Gestion du code JS
	 */
	function loadJavaScript() {
		if ($this->javascript_loaded) {
			return false;
		}
		
		$this->javascript_loaded = true;
		
		
		$html = '
		<script type="text/javascript">
				
				
    /*
    *  Custom object types
    */
				
    function OnlineChess_Board() {
        this.turn = "' . $this->board->turn() . '";
        		
        this.square = new Array(8);
        this.form   = new OnlineChess_Form();
        this.move   = new OnlineChess_Move();
        		
        this.show     = _OnlineChess_Show;
        this.valueOfX = _OnlineChess_Board_valueOfX;
        		
        for (x = 1; x <= 8; x++) {
            this.square[x] = new Array(8);
        		
            for (y = 1; y <= 8; y++) {
                this.square[x][y] = new OnlineChess_Square(x, y);
            }
        }
    }
        		

    function _OnlineChess_Show(piece_color, piece_name) {
        var text = "", color = ((piece_color == "white") ? "#ffd" : "#633"), html = "";	
        html = "<span style=\"color: " + color + ";\" class=\"" + piece_color + "_piece\">" + text + "</span>";
        		
        document.write(html);
    }
      		
        		
        		
    function _OnlineChess_Board_valueOfX(value) {
        value = new String(value);
        		
        if (value.match(/^[1-8]$/)) {
            return String.fromCharCode(64 + parseInt(value));
        } else if (value.match(/^[A-H]$/i)) {
            value = value.toUpperCase();
            return (parseInt(value.charCodeAt(0)) - 64);
        } else {
            return false;
        }
    }
        		
        		
        		
    function OnlineChess_Square(x, y) {
        this.x     = x;
        this.y     = y;
        this.piece = null;
    }
        		
        		
        		
    function OnlineChess_Piece(x, y, color, controlled_squares) {
        this.x     = x;
        this.y     = y;
        this.color = color;
        this.controlled_squares = controlled_squares;
    }
        		
        		
        		
    function OnlineChess_Move(){
        this.start   = false;
        this.end     = false;
        this.removed = "";
        		
        this.go   = _OnlineChess_Move_go;
        this.undo = _OnlineChess_Move_undo;
    }
        		
    function _OnlineChess_Move_go() {
        start_square = document.getElementById("square_" + this.start.charAt(0) + this.start.charAt(1));
        end_square   = document.getElementById("square_" + this.end.charAt(0) + this.end.charAt(1));
        		
        this.removed           = end_square.innerHTML;
        end_square.innerHTML   = start_square.innerHTML;
        start_square.innerHTML = "&nbsp;";
    }
        		
    function _OnlineChess_Move_undo() {
        if (this.removed == "") {
            return false;
        }
        		
        start_square = document.getElementById("square_" + this.start.charAt(0) + this.start.charAt(1));
        end_square   = document.getElementById("square_" + this.end.charAt(0) + this.end.charAt(1));
        		
        start_square.innerHTML = end_square.innerHTML;
        end_square.innerHTML   = this.removed;
        this.removed           = "";
        		
        this.start = false;
        this.end   = false;
    }
        		
        		
        		
    function OnlineChess_Form()  {
        this.setStart   = _OnlineChess_Form_setStart;
        this.setEnd     = _OnlineChess_Form_setEnd;
        this.unsetStart = _OnlineChess_Form_unsetStart;
        this.unsetEnd   = _OnlineChess_Form_unsetEnd;
    }
        		
    function _OnlineChess_Form_setStart() {
        value = Board.valueOfX(Board.move.start.charAt(0)) + String(Board.move.start.charAt(1));
        document.getElementById("mov_start").value = value;
    }
        		
    function _OnlineChess_Form_setEnd() {
        value = Board.valueOfX(Board.move.end.charAt(0)) + String(Board.move.end.charAt(1));
        document.getElementById("mov_end").value = value;
    }
        		
    function _OnlineChess_Form_unsetStart() {
        document.getElementById("mov_start").value = "";
    }
        		
    function _OnlineChess_Form_unsetEnd() {
        document.getElementById("mov_end").value = "";
    }
        		
        		
        		
        		
    /*
    * Custom functions
    */
    function highlightSquare(x, y, highlight) {
        if (highlight == null) {
            highlight = true;
        }
        		
        if (Board.move.end) {
            return false;
        } else if (Board.move.start) {
            x_start = Board.move.start.charAt(0);
            y_start = Board.move.start.charAt(1);
        		
            controlled_squares = Board.square[x_start][y_start].piece.controlled_squares.toString();
        		
            if (!controlled_squares.match(  eval("/" + String(x) + String(y) + "/")  )) {
                return false;
            }
        } else {
            piece = Board.square[x][y].piece;
        		
            if ((piece == null)  ||  (piece.color != Board.turn)  ||  (piece.controlled_squares.length == 0)) {
                return false;
            }
        }
        		
        		
        objStyle = document.getElementById(("square_" + x) + y).style;
        		
        objStyle.border = ((highlight)  ?  "1px solid #00f"  :  "");
        objStyle.cursor = "pointer";
    }
        		
        		
        		
        		
        		
    function selectSquare(x, y) {
        var coordinate = String(x) + String(y);
        		
        if (Board.move.end) {
            if ((coordinate != Board.move.start)  &&  (coordinate != Board.move.end)) {
                x_start = Board.move.start.charAt(0);
                y_start = Board.move.start.charAt(1);
                x_end   = Board.move.end.charAt(0);
                y_end   = Board.move.end.charAt(1);
        		
        		
                document.getElementById("square_" + String(x_start) + String(y_start)).style.border = "";
                document.getElementById("square_" + String(x_end) + String(y_end)).style.border = "";
        		
                Board.move.undo();
                Board.form.unsetStart();
                Board.form.unsetEnd();
            }
        		
            return true;
        } else if (Board.move.start) {
            x_start = Board.move.start.charAt(0);
            y_start = Board.move.start.charAt(1);
        		
            controlled_squares = Board.square[x_start][y_start].piece.controlled_squares.toString();
        		
            if (!controlled_squares.match(  eval("/" + coordinate + "/")  )) {
                document.getElementById("square_" + String(x_start) + String(y_start)).style.border = "";
        		
                Board.move.start = false;
                Board.form.unsetStart();
        		
                return false;
            }

            document.getElementById("square_" + coordinate).style.border = "1px solid #f00";
        		
            Board.move.end = coordinate;
            Board.move.go();
            Board.form.setEnd();
        		
            return true;
        }
        		
        		
        piece = Board.square[x][y].piece;
        		
        if ((piece == null)  ||  (piece.color != Board.turn)  ||  (piece.controlled_squares.length == 0)) {
            return false;
        }
        		
        document.getElementById("square_" + coordinate).style.border = "1px solid #f00";
        		
        Board.move.start = coordinate;
        Board.form.setStart();
        		
        return true;
    }
        			
    /*
    *  Starts main object
    */
        		
    Board = new OnlineChess_Board();
';
		
		if (!$this->board->has_moved  &&  !$this->board->is_check_mate  &&  !$this->board->is_stalemate) {
			$this->board->updateData();
			
			for ($x = 1; $x <= 8; $x++) {
				for ($y = 1; $y <= 8; $y++) {
					if (!$this->board->square[$x][$y]->hasPiece()) {
						continue;
					}
					
					$piece = $this->board->square[$x][$y]->piece;
					
					$controlled_squares = $piece->getControlledSquares($this->board);
					$parameters         = $piece->x . ', ' . $piece->y . ', "' . $piece->color . '", new Array()';
					
					$html .= "\n".'    Board.square[' . $x . '][' . $y . '].piece = new OnlineChess_Piece(' . $parameters . ');';
					
					if (count($controlled_squares) > 0)
					{
						$html .= "\n".'    Board.square[' . $x . '][' . $y . '].piece.controlled_squares';
						$html .= '.push(' . implode(", ", $controlled_squares) . ');';
					}
				}
			}
		}
		
		$html .= "\n\n</script>";
		
		
		return $this->printHTML($html, true);
	}
	
	
	
	/*
	 * Gestion des messages
	 */
	function message($bool_return = false) {
		if (($this->message == "")  &&  (count($this->board->history) > 0))
		{
			$move = end($this->board->history);
			
			$x_start = $move[0];
			$y_start = $move[1];
			$x_end   = $move[2];
			$y_end   = $move[3];
			
			$coordinate = $this->valueOfX($x_start) . $y_start . $this->valueOfX($x_end) . $y_end;
			
			
			$piece_mov = $this->board->square[$x_end][$y_end]->piece;
			$piece_cap = $this->board->removed_piece;
			
			$piece_mov_name = $piece_mov->color . ' ' . $piece_mov->getName(true);
			
			
			if ($piece_mov->isKing()  &&  (abs($x_start - $x_end) == 2)) {
				$this->message = 'Castled ' . $piece_mov_name . ' at ' . $coordinate . '.';
			} else if (!$piece_cap) {
				$this->message = $piece_mov->getName(true) . ' ' . $piece_mov->color . ' se deplace en ' . $coordinate . '.';
			} else {
				$en_passant = "";
				
				if ($piece_mov->isPawn()  &&  (abs($x_start - $x_end) == 1)  &&  ($piece_mov->y != $piece_cap->y)) {
					$en_passant = " &quot;en passant&quot; ";
				}
				
				$this->message  = ucfirst($piece_mov_name) . ' capture ';
				$this->message .= $piece_cap->color . ' ' . $piece_cap->getName(true) . $en_passant . ' a ' . $coordinate . '.';
			}
			
			
			if ($this->board->is_check) {
				if ($this->board->is_check_mate) {
					$this->message .= ' <strong>Echec et mat! ' . ucfirst($piece_mov->color) . ' gagne.</strong>';
				} else {
					$this->message .= ' <strong>Echec!</strong>';
				}
			} else if ($this->board->is_draw) {
				$this->message .= ' <strong>Egalite!</strong>';
			}
		}
		
		return $this->printHTML('<span id="message">' . $this->message . '</span>', $bool_return);
	}

	
	
	/*
	 * Transforme une coordonnees lettre en chifre. Ex : H->8, F->6
	 */
	function valueOfX($value) {
		
		if (preg_match("/^[1-8]$/", $value)) {
			return chr(64 + (int)$value);
		} else if (preg_match("/^[A-H]$/i", $value)) {
			return (ord(strtoupper($value)) - 64);
		} else {
			return false;
		}
	}
	
	
	/*
	 * Cree et renvoie le code HTML affichant une piece
	 */
	private function printPiece($piece) {
		if (!is_object($piece)) {
			return false;
		}
		
		$piece_file_gif = $this->image_dir . $piece->color . '_' . $piece->getName() . '.gif';
		$piece_file_png = $this->image_dir . $piece->color . '_' . $piece->getName() . '.png';
		$piece_file_jpg = $this->image_dir . $piece->color . '_' . $piece->getName() . '.jpg';
		
		if (is_file($piece_file_gif)) {
			$piece_file = $piece_file_gif;
		} elseif ($piece_file_png) {
			$piece_file = $piece_file_png;
		} elseif ($piece_file_jpg) {
			$piece_file = $piece_file_jpg;
		}
		
		$html = '<img src="' . $piece_file . '" alt="" class="' . $piece->color . '_piece" />';
		
		return $html;
	}
	
	
	
	/*
	 * Echo d'une chaine de caractere
	 */
	function printHTML($html, $bool_return = false) {
		if ($bool_return) {
			return $html;
		} else {
			echo $html;
			return true;
		}
	}
	
	
	/*
	 * echo d'un message d'erreur
	 */
	
	function error($message = "")
	{
		$message = ($message != "")  ?  (" ".$message)  :  "";
		
		echo "\n<p>\n    ";
		echo '<span style="padding: 1px 7px; background-color: #ffd7d7; font-family: Verdana;';
		echo ' font-weight: normal; color: #000; font-size: 13px;">';
		echo '<span style="color: #f00; font-weight: bold;">Error!</span>' . $message . '</span>';
		echo "\n</p>\n";
	}
}
?>