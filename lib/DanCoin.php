<?php
	require_once("SecureSessionHandler.php");
    require_once('jsonRPCClient.php');
    require_once ('../vendor/autoload.php');
    //require_once('OAuth.php');
    require_once ('JWT.php');
    require_once('BlackJack1.php');
    
    
    function randomKey($length=32) {
      if(function_exists('openssl_random_pseudo_bytes')) {
        $rnd = openssl_random_pseudo_bytes($length, $strong);
        if ($strong === true) {
          return $rnd;
        }
      }
      return mcrypt_create_iv($length, MCRYPT_DEV_URANDOM);
    }
    
    class DanDatabase {
    
    	private $dsn = 'mysql:host=localhost;dbname=webdata;charset=utf8';
		private $usr = $_ENV['db_user'];
		private $pwd = $_ENV['db_pass'];
		public $pdo;
		private $insertId;
    
    	public function __construct() {
    		$this->pdo = new Slim\PDO\Database($this->dsn, $this->usr, $this->pwd);
    	}
    	
    	public function getGameState($token) {
    		$selectStatement = $this->pdo->select()
                       ->from('players')
                       ->where('token', '=', $token);

			$stmt = $selectStatement->execute();
			$data = $stmt->fetch();

			if ($data) {
				return $data;
			} else {
				return false;
			}
    	}
    	
    	public function insertNewGameToken($token)
    	{
    		$ipv4 = $_SERVER['REMOTE_ADDR'];
    		$token_expire = date("Y-m-d H:i:s", strtotime("+24 hour"));
    		// INSERT INTO users ( wallet , token , token_expire ) VALUES ( ? , ? , ?, ? )
    		
    		try {
    			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    			$this->pdo->beginTransaction();
    			$insertStatement = $this->pdo->insert(array('token', 'token_expire', 'ipv4'))
					->into('players')
					->values(array($token,$token_expire,$ipv4));
				$insertId = $insertStatement->execute();
				//$this->pdo->commit();
			} catch (PDOException $e) {
			
    			//die( 'Connection failed: ' . $e->getMessage());
    			//$this->pdo->rollBack();
    			return false;
    		}

    		$this->__set("insertId", $insertId);
			return true;	
    	}
    	
    	public function deleteRecord($token = "Unset") {
    		if ($token == "Unset" || $token == null) 
    			return false;
    		try {
    			
    			// DELETE FROM users WHERE id = ?
				$deleteStatement = $this->pdo->delete()
                       ->from('players')
                       ->where('token', '=', $token);

				$affectedRows = $deleteStatement->execute();   			
    		} catch (Exception $e) {
    			return false;
    		}
    		
    		return true;
    	}
    	
    	
    	public function updateGameToken($token = "Unset", $gameState = null) {
    		if ($token == "Unset" || $gameState == null || $token == null)
    			return false;
    		try {
    			$token_expire = date("Y-m-d H:i:s", strtotime("+24 hour"));
    			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    			$this->pdo->beginTransaction();
				// UPDATE users SET pwd = ? WHERE id = ?
				$updateStatement = $this->pdo->update(array('token_expire' => $token_expire,'gamestate' => json_encode($gameState)))
					->table('players')
                	->where('token', '=', $token);
				$affectedRows = $updateStatement->execute();
				$this->pdo->commit();
    		} catch (Exception $e) {
    			$this->pdo->rollBack();
    			return false;
    		}
    		
    		return true;
    	}
    	
    	public function commitTransaction() {
    		try {
    			$this->pdo->commit();
    			return true;
    		} catch (Exception $e) {
    			$this->pdo->rollBack();
    			return false;
    		}
    	}
    	
    	public function rollbackTransaction() {
    		try {
    			$this->pdo->rollBack();
    			return true;
    		} catch (Exception $e) {
    			return false;
    		}
    	}
    	
    	public function updateGameSquence($token, $game)
    	{
    		$ipv4 = $_SERVER['REMOTE_ADDR'];
    		$token_expire = date("Y-m-d H:i:s", strtotime("+24 hour"));
    		// INSERT INTO users ( wallet , token , token_expire ) VALUES ( ? , ? , ?, ? )
    		
    		try {
    			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    			$this->pdo->beginTransaction();
    			$insertStatement = $this->pdo->insert(array('token', 'token_expire', 'ipv4'))
					->into('players')
					->values(array($token,$token_expire,$ipv4));
				$insertId = $insertStatement->execute();
				$this->pdo->commit();
			} catch (PDOException $e) {
    			//die( 'Connection failed: ' . $e->getMessage());
    			$this->pdo->rollBack();
    			return false;
    		}
    		
    		$this->__set("insertId", $insertId);
			return $insertId;	
		}
    	
    	public function isTokenInDB($token, $session) {
    		// SELECT * FROM users WHERE id = ?
			$selectStatement = $this->pdo->select()
                       ->from('players')
                       ->where('token', '=', $token);

			$stmt = $selectStatement->execute();
			$data = $stmt->fetch();

			if ($data) {
				$ipv4 = $_SERVER['REMOTE_ADDR'];
				if (time() >= strtotime($data['token_expire']) || $ipv4 != $data['ipv4']) {
				
					// DELETE FROM users WHERE id = ?
				//	$deleteStatement = $this->pdo->delete()
                  //     ->from('players')
                   //    ->where('token', '=', $token);
					//$affectedRows = $deleteStatement->execute();
					return false;
				} else {
						$session->put("player_name",$data['player_name']);
						$session->put("balance",$data['balance']);
						$session->put("wallet",unserialize($data['wallet']));
						$token_expire = date("Y-m-d H:i:s", strtotime("+1 hour"));
						// UPDATE users SET pwd = ? WHERE id = ?
						$updateStatement = $this->pdo->update(array('token_expire' => $token_expire))
							->table('players')
                       		->where('token', '=', $token);

						$affectedRows = $updateStatement->execute();
						return true;				
				}
			}
			else
				return false;
    	}
    		
    	public function __get($property) {
    		if (property_exists($this, $property)) {
     			return $this->$property;
    		}
  		}

  		public function __set($property, $value) {
    		if (property_exists($this, $property)) {
      			$this->$property = $value;
    		}

    		return $this;
  		}
    
    } //DanDatabase
    
    class DanCoin {
    
    	private $username = $_ENV['RPC_USERNAME'];
    	private $password = $_ENV['password'];
    	private $btcserver = $_ENV['BTC_WALLET'];
    	private $btcport = $_EVN['BTC_PORT'];
    	private $player = "Anonymous";

    	private $token = "Unset";
    	private $access_token = null;
    	private $session = null;
    	private $wallet = null;
    	private $balance = 0;
    	private $minbet = .01;
    	private $gameOver = 0;
    	private $userHand = array();
    	private $dealerHand = array();
    	private $gameState;
    	private $db = null;
    	
    	
    	public function __construct()
    	{
    		
    		if ($this->validateToken()) 
    			try {
    				$at = JWT::decode($_SERVER['HTTP_AUTHORIZATION'], $_ENV['token_key']);
    				if ($at) {
    					$this->__set("access_token",$at);
    					$this->__set("token",$this->__get("access_token")->token);	
    					$this->__set("wallet",$this->__get("access_token")->wallet);
    					$this->__set("player",$this->__get("access_token")->player);
    				} //if access token
    			} catch (Exception $e) {
					$this->__set("token","Unset");
    			}
    	} //construct
    	
    	function createPlayer($player = "Anonymous") {
    		try {
    			$this->__set("token",bin2hex(randomKey()));
    			$this->__set("wallet",$this->getWallet($this->__get("token")));
    			$this->__set("player",$player);
    			
    			$at = array();
    			$at['token'] = $this->__get("token");
    			$at['player'] = $this->__get("player");
    			$at['wallet'] = $this->__get("wallet");
    			$date = new DateTime();
				$at['exp'] = strtotime('+24 hours', $date->getTimestamp());
				$at['iss'] = "botjack.co";
				
    			$this->__set("access_token",JWT::encode($at, $_ENV['token_key']));
    			return true;
    		} catch (Exception $e) {
    			return false;
    		}
    	} //createPlayer
    	
    	function validateToken() {
    		try {	
				$at = JWT::decode($_SERVER['HTTP_AUTHORIZATION'], $_ENV['token_key']);
			} catch (Exception $e) {
				return false;
			}
		
			$date = new DateTime();
			
			if ($date->getTimestamp() > $at->exp)
				return false;
				
			if ($at->iss != "botjack.co")
				return false;
				
			try {
				$addresses = $this->getWalletsFromAccount($at->token);
				if ($addresses == null || $addresses[0] != $at->wallet)
					return false;
			} catch (Exception $e) {
				return false;
			}
				
			return true;
    	}
    	
    	function getPlayStatus() {
    		if ($this->validateToken()) {
    			$db = new DanDatabase();
    			$data = $db->getGameState($this->__get("token"));
    			if ($data == false)
    				return false;
    			
    			$gameState = json_decode($data['gamestate']);
    			$this->__set('gameState',$gameState);
    			$this->__set('userHand',$gameState->userHand);
    			
    			$dh = $gameState->dealerHand;
    			if ($gameState->retVal == 0)
    				$dh[0] = 'XX';
    			$this->__set('dealerHand',$dh);
    			
    			$balance = $this->getAccountBalance($this->__get("token"));
       				if ($balance == null)
       					return false;
       				else
       					$this->__set('balance',$balance);
       					
    			
    		} else
    			return false;
    			
    		return true;
    	}
    	
    	function dealNewGame() {
    		if ($this->validateToken()) 
    			if ($this->makeBet($this->__get("token"))) {
    				$error = false;
    				switch ($this->startNewGame($this->__get("token"))) {
    					case 0:
    						$dh = $this->__get('dealerHand');
    						$dh[0] = 'XX';
        					$this->__set('dealerHand',$dh);
						break;
						case 1:
							if($this->transferBetFunds("escrow",md5($this->__get['token']),$dc->__get['minbet']*2) == false)
								$error = true;
							else
								$db->deleteRecord($this->__get("token"));
						break;
						case 2:
							if($this->transferBetFunds("escrow","bitsman",$dc->__get['minbet']*2) == false)
								$error = true;
							else
								$db->deleteRecord($this->__get("token"));
						break;
						case 3:
							if($this->transferBetFunds("escrow","bitsman",$dc->__get['minbet']*2) == false)
								$error = true;
							if($this->transferBetFunds("escrow",md5($this->__get['token']),$dc->__get['minbet']*2) == false)
								$error = true;
							else
								$db->deleteRecord($this->__get("token"));
						break;
       					default:
       						$error = true;
       				} //switch

       				$balance = $this->getAccountBalance($this->__get("token"));
       				if ($balance == null)
       					$error = true;
       				else
       					$this->__set('balance',$balance);
       					
       				$this->__set("access_token",JWT::encode($this->__get("access_token"), $_ENV['token_key']));
       					
       				if ($error)
       					return false;
       				else
       					return true;
    			} //makeBet true
    		return false;
    	} //dealNewGame
    	
    	function hitme() {
    		if ($this->validateToken())
    			if ($this->validHitMe($this->__get("token"))) {
    				$error = false;

					$gs = $this->__get("gameState");
    				$game = new Game();		// Create a new deck and start a new game
    				$game->DECK = $gs->deck;
    				$db = new DanDatabase();
    				$gameOver = false;
    				
    				array_push($gs->userHand,$game->dealCard());
    				$gs->deck = $game->DECK;
    				
    				$this->__set("userHand", $gs->userHand);
    				$this->__set("dealerHand", $gs->dealerHand);
		
					$gs->deck = $game->DECK;
					$gs->dHandValue = $game->getHandValue($gs->dealerHand);
					$gs->uHandValue = $game->getHandValue($gs->userHand);

					$amount = $this->__get('minbet') * 2;
					if ($gs->uHandValue > 21) { 
						$gameOver = true;
						if($this->transferBetFunds("escrow","bitsman",$amount) == false) {
								$error = true;
						} else
							$db->deleteRecord($this->__get("token"));
					}

       				$balance = $this->getAccountBalance($this->__get("token"));
       				if ($balance == null)
       					$error = true;
       				else
       					$this->__set('balance',$balance);
       				
       				if ($gameOver == false)	
       					if ($db->updateGameToken($this->__get("token"),$gs) == false) {
    						$error = true;
    					} else {
    						$dh = $this->__get('dealerHand');
    						$dh[0] = 'XX';
        					$this->__set('dealerHand',$dh);
       					}
        			
        			$this->__set("access_token",JWT::encode($this->__get("access_token"), $_ENV['token_key']));
       					
       				if ($error)
       					return false;
       				else {
       					if ($gs->uHandValue == 21)
							$this->stand();
							return true;
       				}
    			} //validHitMe
    		
    		return false;
    			
    	} //hitme
    	
    	function stand() {
    		if ($this->validateToken())
    			if ($this->validStand($this->__get("token"))) {
    				$error = false;

					$gs = $this->__get("gameState");
    				$game = new Game();		// Create a new deck and start a new game
    				$game->DECK = $gs->deck;
    				$db = new DanDatabase();
    				$gameOver = false;
    				
    				
    				while ($game->getHandValue($gs->dealerHand) < 17) {
    					array_push($gs->dealerHand,$game->dealCard());
    				}
    				
    				$gs->deck = $game->DECK;
    				
    				$this->__set("userHand", $gs->userHand);
    				$this->__set("dealerHand", $gs->dealerHand);
		
					$gs->dHandValue = $game->getHandValue($gs->dealerHand);
					$gs->uHandValue = $game->getHandValue($gs->userHand);


					$amount = $this->__get('minbet') * 2;
					
					if ($gs->dHandValue == $gs->uHandValue) { 
						$gameOver = true;
                        if($this->transferBetFunds("escrow",md5($this->__get("token")),$amount) == false) {
                        	$error = true;
                        } else
                        	if($this->transferBetFunds("escrow","bitsman",$amount) == false) {
                        		$error = true;
                            } else
                            	$db->deleteRecord($this->__get("token"));
					} elseif ($gs->dHandValue > 21) { 
						$gameOver = true;
						if($this->transferBetFunds("escrow",md5($this->__get("token")),$amount) == false) {
								 $error = true;
						} else
							$db->deleteRecord($this->__get("token"));
					} elseif ($gs->dHandValue < $gs->uHandValue) { 
						$gameOver = true;
						if($this->transferBetFunds("escrow",md5($this->__get("token")),$amount) == false) {
								$error = true;
						} else
							$db->deleteRecord($this->__get("token"));
					} elseif ($gs->dHandValue > $gs->uHandValue) { 
						$gameOver = true;
						if($this->transferBetFunds("escrow","bitsman",$amount) == false) {
								$error = true;
						} else
							$db->deleteRecord($this->__get("token"));
					}

       				$balance = $this->getAccountBalance($this->__get("token"));
       				if ($balance == null)
       					$error = true;
       				else
       					$this->__set('balance',$balance);
       				
       				if ($gameOver == false)	
       					if ($db->updateGameToken($this->__get("token"),$gs) == false) {
    						$error = true;
    					}
    					
        			$this->__set("access_token",JWT::encode($this->__get("access_token"), $_ENV['token_key']));
       				
       					
       				if ($error)
       					return false;
       				else 
       					return true;
    			}
    		}// stand
    	
    	function validStand($token) {
    		if ($token == "Unset")
    			return null;
    		$db = new DanDatabase();
    		// Establish defaults
			
			$gameState = $this->getGameState($token);
			if (is_array($gameState)) {
				$date = new DateTime();
				if (!isset($gameState['gamestate']) || !isset($gameState['token_expire']) || $date->getTimestamp() > strtotime($gameState['token_expire']))
					return false;
				$this->__set("gameState",json_decode($gameState['gamestate']));
				return true;
			}
    	} //validStand
    	
    	function validHitMe($token) {
    		if ($token == "Unset")
    			return null;
    		$db = new DanDatabase();
    		// Establish defaults
			
			$gameState = $this->getGameState($token);
			if (is_array($gameState)) {
				$date = new DateTime();
				if (!isset($gameState['gamestate']) || !isset($gameState['token_expire']) || $date->getTimestamp() > strtotime($gameState['token_expire']))
					return false;
				$this->__set("gameState",json_decode($gameState['gamestate']));
				return true;
			}
    	} //validHitMe
    	
    	
    	function getGameState($token) {
    		$db = new DanDatabase();
    		$gameState = $db->getGameState($token); 
			if (is_array($gameState)) {
    			$this->__set("gameState",$gameState);
    			return $gameState;
    		}
    		else 
    			return false;
    	}
    	
    	function revokeGameToken($token) {
    		return true;
    	}
    	
    	function updateGameToken($token) {
    		return true;
    	}
    	
    	function startNewGame($token = "Unset") {
    		if ($token == "Unset")
    			return null;
    		$db = new DanDatabase();
    		// Establish defaults

			$game = new Game();		// Create a new deck and start a new game
			$retVal = 0;
    		/**initial deal**/
    		$userHand[0] = $game->dealCard();
    		$dealerHand[0] = $game->dealCard();
    		$userHand[1] = $game->dealCard();
    		$dealerHand[1] = $game->dealCard();

    	//	$_SESSION['userHand'] = $userHand;
    		$this->__set("userHand", $userHand);
    	//	$_SESSION['dealerHand'] = $dealerHand;
    		$this->__set("dealerHand", $dealerHand);
		//	$_SESSION['dHandValue'] = $game->getHandValue(dealerHand);
			$dHandValue = $game->getHandValue($dealerHand);
			$uHandValue = $game->getHandValue($userHand);

			if ($dHandValue == 21)
				$retVal = 2;
				
			if ($uHandValue == 21)
				$retVal = 1; 
				
			if ($uHandValue == 21 && $dHandValue == 21)
				$retVal = 3;
				
			$gameState = array();
			$gameState['userHand'] = $userHand;
			$gameState['dealerHand'] = $dealerHand;
			$gameState['dHandValue'] = $dHandValue;
			$gameState['uHandValue'] = $uHandValue;
			$gameState['retVal'] = $retVal;
			$gameState['deck'] = $game->DECK;
			if ($db->updateGameToken($token,$gameState) == false)
    			return null;
    			
			$this->__set("gameOver", $retVal);
			$this->__set("gameState", $gameState);
			return $retVal;
    	} //startNewGame
    	
    	function makeBet($token = "Unset") {
    		if ($token == "Unset")
    			return false;
    			
    		if ($this->getGameState($token) != false)
    			return false;

    		if ($this->insertNewGameToken($token) == false)
    			return false;

    		if ($this->getHouseBalance() < 4 * $this->__get("minbet"))
    			return false;

    		if ($this->getAccountBalance($token) < $this->__get("minbet"))
    			return false;
    		
    		if ($this->transferBetFunds(md5($token), "escrow", $this->__get("minbet")) == false)
    			return false;
    			
    		if ($this->transferBetFunds("bitsman", "escrow", $this->__get("minbet")) == false)
    			return false;
    			
    		if ($this->__get("db") != null)
    			if($this->commitDBChange($this->__get("db")) == false)
    				return false;
    				
    		return true;
    	}
    	
    	function commitDBChange($db) {
    		return $db->commitTransaction();
    	}
    	
    	function insertNewGameToken($token) {
    		$db = new DanDatabase();
    		
    		if($db->insertNewGameToken($token)) {
    			$this->__set("db",$db);
    			return true;
    		}
    		else {
    			$this->__set("db",null);
    			return false;
    		}
    	}
    	
    	function transferBetFunds($from, $to, $amount = 0 ) {
    		try {
				$bitcoind = new jsonRPCClient('http://' . $this->__get("username") . ':' . $this->__get("password") . '@' . $this->__get("btcserver") . ':' . $this->__get("btcport") .'/');
				if ($bitcoind->move($from, $to, $amount))
					return true;
				else
					return false;
			} catch (Exception $e) {
					return false;
			}
    	}
    	
    	function getBalance() {
			if ($this->validateToken()) {
				$ab = $this->getAccountBalance($this->__get("token"));
				if ($ab === null)
					return false;
				$this->__set("balance", $ab);
				$this->__set("access_token",JWT::encode($this->__get("access_token"), $_ENV['token_key']));
				return true;
			} else {
				return false;
			}
    	} //getBalance
    	
    	function cashout($sendaddress) {
			if ($this->validateToken()) {
				$ab = $this->getAccountBalance($this->__get("token"));
				if ($ab === null)
					return false;
					
				if ($ab - '.0004' < 0)
					return false;
				else
					$ab = $ab - .0004;
					
				try {
					$bitcoind = new jsonRPCClient('http://' . $this->__get("username") . ':' . $this->__get("password") . '@' . $this->__get("btcserver") . ':' . $this->__get("btcport") .'/');
					$balance = $bitcoind->sendfrom(md5($this->__get("token")),$sendaddress,$ab,2);
					
				} catch (Exception $e) {
					return false;
				}
				
				$this->__set("balance", $ab);
				$this->__set("access_token",JWT::encode($this->__get("access_token"), $_ENV['token_key']));
				return true;
			} else {
				return false;
			}
    	} //cashout

    	function getWallet($token = "Unset") {
    		if($token == "Unset")
    			return null;
    		else {
    			$bitcoind = new jsonRPCClient('http://' . $this->__get("username") . ':' . $this->__get("password") . '@' . $this->__get("btcserver") . ':' . $this->__get("btcport") .'/');
				$newaddr = $bitcoind->getnewaddress(md5($token));
				return $newaddr;
			}
			return null;
    	} //getWallet
    	
    	function getWalletsFromAccount($token = "Unset") {
    		if($token == "Unset")
    			return null;
    		else {
    			$bitcoind = new jsonRPCClient('http://' . $this->__get("username") . ':' . $this->__get("password") . '@' . $this->__get("btcserver") . ':' . $this->__get("btcport") .'/');
				$newaddr = $bitcoind->getaddressesbyaccount(md5($token));
				return $newaddr;
			}
			return null;
    	} //getWallet
    	
    	function getAccountBalance($token = "Unset") {
    	
    		if ($token == "Unset")
    			return null;
    		try {
    			$bitcoind = new jsonRPCClient('http://' . $this->__get("username") . ':' . $this->__get("password") . '@' . $this->__get("btcserver") . ':' . $this->__get("btcport") .'/');
				$balance = $bitcoind->getbalance(md5($token),2);
				return $balance;
			} catch (Exception $e) {
				return null;
			}
			
    	} //getWallet
    	
    	function getHouseBalance() {
    		try { 
    			$bitcoind = new jsonRPCClient('http://' . $this->__get("username") . ':' . $this->__get("password") . '@' . $this->__get("btcserver") . ':' . $this->__get("btcport") .'/');
				$balance = $bitcoind->getbalance("bitsman");
				return $balance;
			} catch (Exception $e) {
				return 0;
			}
    	}
    	
    	public function __get($property) {
    		if (property_exists($this, $property)) {
     			return $this->$property;
    		}
  		}

  		public function __set($property, $value) {
    		if (property_exists($this, $property)) {
      			$this->$property = $value;
    		}

    		return $this;
  		}
  		
    } //DanCoin class

?>
