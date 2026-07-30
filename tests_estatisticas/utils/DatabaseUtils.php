<?php

class DatabaseUtils
{
    private $connection;

  public function __construct($nomeContexto)
    {
    try {
        $dns = getenv($nomeContexto."_DSN");
        $user = getenv($nomeContexto."_DATABASE_USER");
        $password = getenv($nomeContexto."_DATABASE_PASSWORD");
        $this->connection = new PDO($dns, $user, $password);

    } catch (PDOException $e) {
        echo "Erro na conexão: " . $e->getMessage();
    }
  }


  public function execute($sql, $params = array()){
      $statement = $this->connection->prepare($sql);
      $result = $statement->execute($params);

      return $result;
  }


  public function query($sql, $params = array()){
      $statement = $this->connection->prepare($sql);
      $statement->execute($params);
      return $statement->fetchAll();
  }   

    
  public function getBdType(){
      return $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME);
  }
}
