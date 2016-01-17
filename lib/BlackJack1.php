<?PHP

class Game
{
	public $DECK = array();
	public $DEALER = array();
	public $PLAYER = array();
	public $cards = array("A","2","3","4","5","6","7","8","9","10","J","Q","K");
	public $suits = array("D","H","S","C");
	
	public function Game()
	{
		// Create the deck
		$this->createDeck();
		// Shuffle the deck 4 times just to be good and shuffled
		for ($t = 0; $t <= 3; $t++)
		{
			shuffle($this->DECK);
		}
	}
	
	private function createDeck()
	{
		for ($i = 0; $i < 13; $i++)
		{
			// 13x4 = 52 cards
			for($x = 0; $x < 4; $x++)
			{
				// Cycle through suits
				// $cards[$i] = current card type
				// $suits[$x] = current suit
				array_push($this->DECK,$this->cards[$i].$this->suits[$x]);
			}
		}
	}
	
	public function dealDealer()
	{
		return array_pop($this->DECK).",".array_pop($this->DECK);
	}
	
	public function dealPlayer()
	{
		return array_pop($this->DECK).",".array_pop($this->DECK);
	}
	
	public function dealCard()
	{
		return array_pop($this->DECK);
	}
	
	public function translateCard($card)
	{
		$face = substr($card,0,-1);
		$suit = substr($card,-1,1);
		switch($suit)
		{
			case 'C':
				return $face." of Clubs";
			case 'S':
				return $face." of Spades";
			case 'H':
				return $face." of Hearts";
			case 'D':
				return $face." of Diamonds";
		}
	}
	
	public function getHandValue($cards)
	{
		sort($cards);
		
		$value = 0;
		foreach ($cards as &$values)
		{
			$value += $this->getCardValue($values);
			if ($value > 21 && preg_match("/A/", $values))
				$value = $value - 10;
		}

		return $value;
	}
	
	public function getCardValue($card)
	{
		$face = substr($card,0,-1);
		$suit = substr($card,-1,1);
		$num_pattern = '/[0-9]/';
		$face_pattern = '/[JQK]/';
		if (preg_match($num_pattern,$face))
		{
			// This is a number card
			return $face;
		}
		else if (preg_match($face_pattern,$face))
		{
			// This is a regular face card value of 10
			return 10;
		}
		else
		{
			return 11;
		}
		echo "Face: ".$face."<br />Suit: ".$suit."<br />";
	}
	
	/**returns 1 if game is over, 0 if no victory conditions are met**/
    public function winCheck($uValue, $dValue, $stand){
        if($uValue > 21){
            /**YOU LOSE**/
            echo "<div style='background-color:red; text-align:center; color:white; font-size:26px; font-weight:bold; padding:20px;'>You Lose!!!</div>";
            return 1;

        }
        else if ($dValue > 21){
            /**YOU WIN**/
            echo "<div style='background-color:green; text-align:center; color:white; font-size:26px; font-weight:bold; padding:20px;'>You Win!!!</div>";
            return 1;
        }
        else if ($stand == 1){
            if($uValue > $dValue){
                /**YOU WIN**/
                echo "<div style='background-color:green; text-align:center; color:white; font-size:26px; font-weight:bold; padding:20px;'>You Win!!!</div>";
                return 1;
            }
            else{
                /**YOU LOSE**/
                echo "<div style='background-color:red; text-align:center; color:white; font-size:26px; font-weight:bold; padding:20px;'>You Lose!!!</div>";
                return 1;
            }
        }
        return 0;
    }
}
