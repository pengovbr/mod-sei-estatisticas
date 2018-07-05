<?
require_once dirname(__FILE__).'/../../../SEI.php';


class MdEstatisticasColetarRN extends InfraRN {

  public function __construct(){
    parent::__construct();
  }

  protected function inicializarObjInfraIBanco(){
    return BancoSEI::getInstance();
  }

  public function coletarIndicadores() {

    try {

      $objConfiguracaoSEI = ConfiguracaoSEI::getInstance();
      $orgaoSigla = $objConfiguracaoSEI->getValor('MdEstatisticas','sigla', false, '');
      
      $ind = array();
      
      $ind['dataColeta'] = $this->obterDataColeta();
      $ind['orgaoSigla'] = $orgaoSigla;
      $ind['seiVersao'] = $this->obterVersaoSEI();
      $ind['phpVersao'] = $this->obterVersaoPHP();
      $ind['memcachedVersao'] = $this->obterVersaoMemcached();
      $ind['solrVersao'] = $this->obterVersaoSolr();
      $ind['protocolo'] = $this->obterProtocolo();
      $ind['quantidadeUnidades'] = $this->obterQuantidadeUnidades();
      $ind['quantidadeProcedimentos'] = $this->obterQuantidadeProcessosAdministrativos();
      $ind['quantidadeUsuarios'] = $this->obterQuantidadeUsuarios();
      $ind['quantidadeDocumentosInternos'] = $this->obterQuantidadeDocumentosInternos();
      $ind['quantidadeDocumentosExternos'] = $this->obterQuantidadeDocumentosExternos();
      $ind['quantidadeMemoria'] = $this->obterUsoMemoria();
      $ind['porcentagemCPU'] = $this->obterUsoCPU();
      $ind['estrategiaCessao'] = $this->obterEstrategiaCessao();
      $ind['tamanhoDatabase'] = $this->obterTamanhoDataBase();
      $ind['bancoSei'] = $this->obterTipoSGBD();
      $ind['bancoVersao'] = $this->obterBancoVersao();
      $ind['servidorAplicacao'] = $this->obterServidorAplicacao();
      $ind['sistemaOperacional'] = $this->obterSistemaOperacional();
      $ind['sistemaOperacionalDetalhado'] = $this->obterSistemaOperacionalDetalhado();
      $ind['tamanhoFilesystem'] = $this->obterTamanhoFileSystem();
      $ind['tabelasTamanhos'] = $this->obterTamanhoTabelas();
      $ind['modulos'] = $this->obterPlugins();
      $ind['extensoes'] = $this->obterQuantidadeDocumentosExternosPorExtensao();
      $ind['anexosTamanhos'] = $this->obterTamanhoDocumentosExternos();
      
      InfraDebug::getInstance()->gravar('Ind: ' . json_encode($ind), InfraLog::$INFORMACAO);

      return $ind;

    } catch(Exception $e) {
      InfraDebug::getInstance()->setBolLigado(false);
      InfraDebug::getInstance()->setBolDebugInfra(false);
      InfraDebug::getInstance()->setBolEcho(false);
      throw new InfraException('Erro processando estatísticas do sistema.',$e);
    }
  }

  private function obterVersaoSEI(){
    InfraDebug::getInstance()->gravar('SEI01 - Versão SEI: ' . SEI_VERSAO, InfraLog::$INFORMACAO);
    return SEI_VERSAO;
  }

  private function obterVersaoPHP(){
    InfraDebug::getInstance()->gravar('SEI21 - Versão PHP: ' . phpversion(), InfraLog::$INFORMACAO);
    return phpversion();
  }

  private function getDirectorySize($path){
    $bytestotal = 0;
    $path = realpath($path);
    if($path!==false){
      foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object){
        $bytestotal += $object->getSize();
      }
    }
    return $bytestotal;
  }

  private function obterTamanhoFileSystem(){
    $objConfiguracaoSEI = ConfiguracaoSEI::getInstance();
    if ($objConfiguracaoSEI->isSetValor('SEI', 'RepositorioArquivos')){
      $diretorio = $objConfiguracaoSEI->getValor('SEI','RepositorioArquivos');
      $tamanho = $this->getDirectorySize($diretorio);

      InfraDebug::getInstance()->gravar('SEI02 - Diretorio: ' . $diretorio, InfraLog::$INFORMACAO);
      InfraDebug::getInstance()->gravar('SEI02 - Tamanho File System: ' . $tamanho, InfraLog::$INFORMACAO);
    }
    return $tamanho;
  }

  private function obterPlugins(){
    global $SEI_MODULOS;
    $lista = array();
    foreach($SEI_MODULOS as $strModulo => $seiModulo){
      $result = array(
        'nome' => $strModulo,
        'versao' => $seiModulo->getVersao()
      );
      array_push($lista, $result);
    }

    InfraDebug::getInstance()->gravar('SEI03 - Plugins: ' . json_encode($lista), InfraLog::$INFORMACAO);
    return $lista;
  }

  private function obterQuantidadeUnidades(){

    $objUnidadeRN = new UnidadeRN();
    $numQuantidadeUnidades = $objUnidadeRN->contarRN0128(new UnidadeDTO());

    InfraDebug::getInstance()->gravar('SEI11 - Quantidade Unidades: ' . $numQuantidadeUnidades, InfraLog::$INFORMACAO);
    return $numQuantidadeUnidades;
  }

  private function obterTamanhoTotalDocumentosExternos(){

    $query = "select sum(tamanho) as tamanho from anexo where sin_ativo = 'S'";
    $rs = BancoSEI::getInstance()->consultarSql($query);
    $tamanho = (count($rs) && isset($rs[0]['tamanho'])) ? $rs[0]['tamanho'] : 0;

    InfraDebug::getInstance()->gravar('SEI12 - Tamanho Documentos Externos: ' . $tamanho, InfraLog::$INFORMACAO);
    return $tamanho;
  }

  private function obterQuantidadeUsuarios(){

    $query = "SELECT COUNT(*) as quantidade FROM usuario WHERE sin_ativo = 'S'";
    $rs = BancoSEI::getInstance()->consultarSql($query);
    $quantidade = (count($rs) && isset($rs[0]['quantidade'])) ? $rs[0]['quantidade'] : 0;

    InfraDebug::getInstance()->gravar('SEI09 - Quantidade de usuários: ' . $quantidade, InfraLog::$INFORMACAO);
    return $quantidade;
  }

  private function obterProtocolo(){
    $objConfiguracaoSEI = ConfiguracaoSEI::getInstance();
    if ($objConfiguracaoSEI->isSetValor('SessaoSEI', 'https')){
      $temHTTPS = $objConfiguracaoSEI->getValor('SessaoSEI', 'https');
      $protocolo = 'HTTP';
      if ($temHTTPS) {
        $protocolo = 'HTTPS';
      }
      InfraDebug::getInstance()->gravar('SEI12 - Protocolo: ' . $protocolo, InfraLog::$INFORMACAO);
      return $protocolo;
    }
  }

  private function obterQuantidadeProcessosAdministrativos(){
    $query = "select count(*) as quantidade from procedimento";
    $rs = BancoSEI::getInstance()->consultarSql($query);
    $quantidade = (count($rs) && isset($rs[0]['quantidade'])) ? $rs[0]['quantidade'] : 0;

    InfraDebug::getInstance()->gravar('SEI06 - Quantidade de Processos Administrativos: ' . $quantidade, InfraLog::$INFORMACAO);
    return $quantidade;
  }

  private function obterTipoSGBD(){
    $objConfiguracaoSEI = ConfiguracaoSEI::getInstance();
    $sgbd = $objConfiguracaoSEI->getValor('BancoSEI','Tipo', false, '');
    InfraDebug::getInstance()->gravar('SEI02 - SGBD: ' . $sgbd, InfraLog::$INFORMACAO);
    return $sgbd;
  }

  private function obterQuantidadeDocumentosInternos(){
    $query = "SELECT COUNT(*) as quantidade FROM documento WHERE STA_DOCUMENTO = 'I'";
    $rs = BancoSEI::getInstance()->consultarSql($query);
    $quantidade = (count($rs) && isset($rs[0]['quantidade'])) ? $rs[0]['quantidade'] : 0;

    InfraDebug::getInstance()->gravar('SEI05 - Quantidade de documentos internos: ' . $quantidade, InfraLog::$INFORMACAO);
    return $quantidade;
  }

  private function obterQuantidadeDocumentosExternos(){
    $query = "SELECT COUNT(*) as quantidade FROM documento WHERE STA_DOCUMENTO = 'X'";
    $rs = BancoSEI::getInstance()->consultarSql($query);
    $quantidade = (count($rs) && isset($rs[0]['quantidade'])) ? $rs[0]['quantidade'] : 0;

    InfraDebug::getInstance()->gravar('SEI05 - Quantidade de documentos externos: ' . $quantidade, InfraLog::$INFORMACAO);
    return $quantidade;
  }

  private function obterQuantidadeDocumentosExternosPorExtensao(){
    $query = "SELECT nome FROM anexo WHERE sin_ativo = 'S'";
    $rs = BancoSEI::getInstance()->consultarSql($query);
    $extensoes = array();
    # Calculando na aplicacao para funcionar independente do banco
    foreach($rs as $r) {
      $extensao =pathinfo($r['nome'], PATHINFO_EXTENSION);
      $qtd = $extensoes[$extensao];
      if (!$qtd) {
        $qtd = 0;
      }
      $extensoes[$extensao] = $qtd + 1;
    }
    $lista = array();
    foreach($extensoes as $key => $value) {
      $result = array(
        'extensao' => $key,
        'quantidade' => $value
      );
      array_push($lista, $result);
    }

    InfraDebug::getInstance()->gravar('SEI07 - Quantidade de  extensoes de documentos externos: ' . json_encode($lista), InfraLog::$INFORMACAO);
    return $lista;
  }

  private function obterEstrategiaCessao(){
    InfraDebug::getInstance()->gravar('SEI24 - Estrategia de armazenamento de cessao: ' . ini_get('session.save_handler'), InfraLog::$INFORMACAO);
    return ini_get('session.save_handler');
  }

  private function obterVersaoMemcached(){
    $objConfiguracaoSEI = ConfiguracaoSEI::getInstance();
    $host = $objConfiguracaoSEI->getValor('CacheSEI','Servidor', false, '');
    $porta = $objConfiguracaoSEI->getValor('CacheSEI','Porta', false, '');

    $memcache = new Memcache;
    $memcache->connect($host, $porta);
    $versao = $memcache->getVersion();

    InfraDebug::getInstance()->gravar('SEI23 - Versão memcached: ' . $versao, InfraLog::$INFORMACAO);
    return $versao;
  }

  private function obterTamanhoDatabase(){
    $sgbd = $this->obterTipoSGBD();
    $query = '';
    if ($sgbd == 'MySql') {
      $query = "SELECT table_schema, SUM(data_length + index_length) as tamanho FROM information_schema.TABLES WHERE table_schema = 'sei' GROUP BY table_schema";
    } elseif ($sgbd == 'SqlServer') {
      $query = "SELECT SUM(Total_Pages * 8 * 1000) As tamanho FROM sys.partitions As P INNER JOIN sys.allocation_units As A ON P.hobt_id = A.container_id  INNER JOIN sys.tables t on t.object_id = p.object_id";
    } elseif ($sgbd == 'Oracle') {
      $query = "";
    }
    $rs = array();
    if($query) {
      $rs = BancoSEI::getInstance()->consultarSql($query);
    }
    $tamanho = (count($rs) && isset($rs[0]['tamanho'])) ? $rs[0]['tamanho'] : 0;

    InfraDebug::getInstance()->gravar('SEI03 - Tamanho do SGBD: ' . $tamanho, InfraLog::$INFORMACAO);
    return $tamanho;
  }

  private function obterTamanhoTabelas(){
    $sgbd = $this->obterTipoSGBD();
    $query = '';
    if ($sgbd == 'MySql') {
      $query = "SELECT table_name as tabela, data_length + index_length as tamanho FROM information_schema.TABLES WHERE table_schema = 'sei'";
    } elseif ($sgbd == 'SqlServer') {
      $query = "" .
        " SELECT t.name as tabela,  SUM(Total_Pages * 8 * 1000) As tamanho " .
        " FROM sys.partitions As P " .
        "   INNER JOIN sys.allocation_units As A ON P.hobt_id = A.container_id " .
        "   INNER JOIN sys.tables t on t.object_id = p.object_id " .
        " GROUP BY t.name ORDER BY t.name";
    } elseif ($sgbd == 'Oracle') {
      $query = "";
    }
    $tabelas = array();
    if($query) {
      $tabelas = BancoSEI::getInstance()->consultarSql($query);
    }

    InfraDebug::getInstance()->gravar('SEI15 - Tamanho das tabelas: ' . json_encode($tabelas), InfraLog::$INFORMACAO);
    return $tabelas;
  }


  private function obterVersaoSolr(){
    $objConfiguracaoSEI = ConfiguracaoSEI::getInstance();
    $url = $objConfiguracaoSEI->getValor('Solr','Servidor', false, 'http://localhost:8983/solr');
    $url = $url . '/admin/info/system?wt=json';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    $json = json_decode($output, true);
    $versao = $json['lucene']['lucene-spec-version'];
    InfraDebug::getInstance()->gravar('SEI22 - Versao Solr: ' . $versao, InfraLog::$INFORMACAO);
    return $versao;
  }

  private function obterServidorAplicacao(){
    $versao = $_SERVER['SERVER_SOFTWARE'];
    InfraDebug::getInstance()->gravar('SEI20 - Quantidade de servidores de aplicação e suas versões: ' . $versao, InfraLog::$INFORMACAO);
    return $versao;
  }

  private function obterSistemaOperacional(){
    $so = PHP_OS;
    $versao = $_SERVER['SERVER_SOFTWARE'];
    InfraDebug::getInstance()->gravar('SEI17 - Quantidade de Sistemas Operacionais: ' . $so, InfraLog::$INFORMACAO);
    return $so;
  }

  private function obterSistemaOperacionalDetalhado(){
    $so = php_uname();
    $versao = $_SERVER['SERVER_SOFTWARE'];
    InfraDebug::getInstance()->gravar('SEI17 - Quantidade de Sistemas Operacionais (Detalhado): ' . $so, InfraLog::$INFORMACAO);
    return $so;
  }

  private function obterDataColeta(){
    $dataColeta = date (DATE_ATOM);
    InfraDebug::getInstance()->gravar('SEI29 - Periodicidade do envio - Data da coleta: ' . $dataColeta, InfraLog::$INFORMACAO);
    return $dataColeta;
  }

  private function obterTamanhoDocumentosExternos(){
    $resultado = array();
    # 0MB - !MB
    $query = "SELECT count(*) as quantidade FROM anexo WHERE sin_ativo = 'S' AND tamanho >= 0 AND tamanho < 1000";
    $rs = BancoSEI::getInstance()->consultarSql($query);
    $resultado[0] = array(
      'tamanho' => '0MB - 1MB',
      'quantidade' => (count($rs) && isset($rs[0]['quantidade'])) ? $rs[0]['quantidade'] : 0
    );

    # 1MB - 10MB
    $query = "SELECT count(*) as quantidade FROM anexo WHERE sin_ativo = 'S' AND tamanho >= 1000 AND tamanho < 10000";
    $rs = BancoSEI::getInstance()->consultarSql($query);
    $resultado[1] = array(
      'tamanho' => '1MB - 10MB',
      'quantidade' => (count($rs) && isset($rs[0]['quantidade'])) ? $rs[0]['quantidade'] : 0
    );

    # 10MB - 100MB
    $query = "SELECT count(*) as quantidade FROM anexo WHERE sin_ativo = 'S' AND tamanho >= 10000 AND tamanho < 100000";
    $rs = BancoSEI::getInstance()->consultarSql($query);
    $resultado[2] = array(
      'tamanho' => '10MB - 100MB',
      'quantidade' => (count($rs) && isset($rs[0]['quantidade'])) ? $rs[0]['quantidade'] : 0
    );

    # > 100MB
    $query = "SELECT count(*) as quantidade FROM anexo WHERE sin_ativo = 'S' AND tamanho >= 100000";
    $rs = BancoSEI::getInstance()->consultarSql($query);
    $resultado[3] = array(
      'tamanho' => 'Maior que 100MB',
      'quantidade' => (count($rs) && isset($rs[0]['quantidade'])) ? $rs[0]['quantidade'] : 0
    );

    InfraDebug::getInstance()->gravar('SEI11 - Tamanho dos documentos externos: ' . json_encode($resultado), InfraLog::$INFORMACAO);
    return $resultado;
  }

  private function obterUsoMemoria(){
    $memoria = memory_get_usage();
    InfraDebug::getInstance()->gravar('SEI18 - Quantidade de byte de uso de memoria: ' . json_encode($memoria), InfraLog::$INFORMACAO);
    return $memoria;
  }

  private function obterUsoCPU(){
    $load = sys_getloadavg();
    $uso = null;
    if ($load) {
      $uso = $load[0];
    }
    InfraDebug::getInstance()->gravar('SEI18 - Porcentagem de uso de CPU: ' . json_encode($uso), InfraLog::$INFORMACAO);
    return $uso;
  }

  private function obterBancoVersao(){
    $sgbd = $this->obterTipoSGBD();
    $query = '';
    if ($sgbd == 'MySql') {
      $query = "SELECT version() as versao";
    } elseif ($sgbd == 'SqlServer') {
      $query = "SELECT SERVERPROPERTY('productversion') as versao";
    } elseif ($sgbd == 'Oracle') {
      $query = "select version AS versao from product_component_version WHERE product LIKE 'Oracle%'";
    }
    $rs = array();
    if ($query) {
      $rs = BancoSEI::getInstance()->consultarSql($query);
    }
    $versao = (count($rs) && isset($rs[0]['versao'])) ? $rs[0]['versao'] : null;
    InfraDebug::getInstance()->gravar('SEI02 - Versao do SGBD: ' . $versao, InfraLog::$INFORMACAO);
    return $versao;
  }

  public function obterVelocidadePorCidade(){
    $query = "
      select d.nome as cidade, e.nome as uf, avg(velocidade) as velocidade
      from velocidade_transferencia a
        join unidade b on b.id_unidade = a.id_unidade
        join contato c on b.id_contato = c.id_contato
        join cidade d on c.id_cidade = d.id_cidade
        join uf e on d.id_uf = e.id_uf
      group by
        d.nome, e.nome
    ";
    $rs = BancoSEI::getInstance()->consultarSql($query);
    $lista = array();
    foreach($rs as $r) {
    	$result = array(
    			'cidade' => utf8_encode($r['cidade']),
    			'uf' => utf8_encode($r['uf']),
    			'velocidade' => $r['velocidade']
    	);
    	
    	array_push($lista, $result);
    }
    InfraDebug::getInstance()->gravar('SEI14 - Quantidade de bytes de transferência: ' . json_encode($lista), InfraLog::$INFORMACAO);
    return $lista;
  }

  public function obterAcessosUsuarios($ultimadata=null){
    if ($ultimadata == null) {
      $ultimadata = '1900-01-01';
    }
    $sgbd = $this->obterTipoSGBD();
    $query = '';
    if ($sgbd == 'MySql') {
      $query = "select count(*) as quantidade, date(dth_acesso) as data from infra_navegador where date(dth_acesso) > " . $ultimadata . " group by date(dth_acesso)";
    } elseif ($sgbd == 'SqlServer') {
      $query = "select count(*) as quantidade, CONVERT(date, dth_acesso) as data from infra_navegador where dth_acesso >= " . $ultimadata . " group by CONVERT(date, dth_acesso)";
    } elseif ($sgbd == 'Oracle') {
      $query = "select count(*) as quantidade, to_char(dth_acesso,'YYYY-MM-DD') AS data from infra_navegador where dth_acesso >= date " . $ultimadata . " group by to_char(dth_acesso,'YYYY-MM-DD')";
    }

    $rs = array();
    if($query) {
      $rs = BancoSEI::getInstance()->consultarSql($query);
    }
    InfraDebug::getInstance()->gravar('SEI27 - Quantidade de acessos por dia: ' . json_encode($rs), InfraLog::$INFORMACAO);
    return $rs;
  }
  
  public function obterSistemasOperacionaisUsuarios(){
  	$sgbd = $this->obterTipoSGBD();
  	if ($sgbd == 'Oracle') {
  		$query = "select distinct to_char(user_agent) as nome from infra_auditoria where user_agent is not null";
  	} else {
  		$query = "select distinct user_agent as nome from infra_auditoria where user_agent is not null";
  	}
  	$sistemas = BancoSEI::getInstance()->consultarSql($query);
  	
  	$lista = array();
  	foreach($sistemas as $r) {
  		$texto = $r['nome'];
  		$inicio = strpos($texto, '(');
  		if ($inicio !== false) {
  			$fim = strpos($texto, ')', $inicio);
  			$nome = substr($texto, $inicio + 1, $fim - $inicio -1);
  			array_push($lista, $nome);
  		}
  	}
  	$lista = array_unique($lista);
  	
  	$sistemas = array();
  	foreach($lista as $n) {
  		$result = array('nome'=>$n);
  		array_push($sistemas, $result);
  	}
  	
  	InfraDebug::getInstance()->gravar('SEI26 - Sistemas Operacionais dos Clientes: ' . json_encode($sistemas), InfraLog::$INFORMACAO);
  	return $sistemas;
  }
  
  public function obterNavegadores(){
  	$query = "select count(*) as quantidade, identificacao as nome, versao from infra_navegador group by identificacao,versao";
  	$rs = BancoSEI::getInstance()->consultarSql($query);
  	$lista = array();
  	foreach ($rs as $r) {
  		$r['nome'] = 
  		InfraDebug::getInstance()->gravar('Navegador: ' . json_encode($r) . ' - ' . $r['nome'], InfraLog::$INFORMACAO);
  		$result = array(
  				'nome' => utf8_encode($r['nome']),
  				'quantidade' => $r['quantidade'],
  				'versao' => $r['versao']
  		);
  		array_push($lista, $result);
  	}
  	
  	InfraDebug::getInstance()->gravar('SEI13 - Quantidade de Navegadores: ' . json_encode($lista), InfraLog::$INFORMACAO);
  	return $lista;
  }
  

}
?>
