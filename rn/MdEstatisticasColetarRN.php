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

      $indicadores = array(
        'dataColeta' => $this->obterDataColeta(),
        'seiVersao' => $this->obterVersaoSEI(),
        'phpVersao' => $this->obterVersaoPHP(),
        'memcachedVersao' => $this->obterVersaoMemcached(),
        'solrVersao' => $this->obterVersaoSolr(),
        'protocolo' => $this->obterProtocolo(),
        'quantidadeUnidades' => $this->obterQuantidadeUnidades(),
        'quantidadeProcedimentos' => $this->obterQuantidadeProcessosAdministrativos(),
        'quantidadeUsuarios' => $this->obterQuantidadeUsuarios(),
        'quantidadeDocumentosInternos' => $this->obterQuantidadeDocumentosInternos(),
        'quantidadeDocumentosExternos' => $this->obterQuantidadeDocumentosExternos(),
        'estrategiaCessao' => $this->obterEstrategiaCessao(),
        'tamanhoDatabase' => $this->obterTamanoDataBase(),
        'bancoSei' => $this->obterTipoSGBD(),
        'servidorAplicacao' => $this->obterServidorAplicacao(),
        'sistemaOperacional' => $this->obterSistemaOperacional(),
        'sistemaOperacionalDetalhado' => $this->obterSistemaOperacionalDetalhado(),
        'navegadores' => $this->obterNavegadores(),
        'modulos' => $this->obterPlugins(),
        'tamanhoFilesystem' => $this->obterTamanhoFileSystem(),
        'tamanhoDocumentosExternos' => $this->obterTamanhoDocumentosExternos(),
        'extensoes' => $this->obterQuantidadeDocumentosExternosPorExtensao()
      );

      return $indicadores;

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

  private function obterNavegadores(){
    $query = "select count(*) as quantidade, identificacao, versao from infra_navegador group by identificacao,versao";
    $rs = BancoSEI::getInstance()->consultarSql($query);
    $lista = array();
    foreach($rs as $r) {
      $result = array(
        'quantidade' => (int)$r['quantidade'],
        'nome' => $r['identificacao'],
        'versao' => $r['versao']
      );
      array_push($lista, $result);
    }

    InfraDebug::getInstance()->gravar('SEI13 - Quantidade de Navegadores: ' . json_encode($lista), InfraLog::$INFORMACAO);
    return $lista;
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

  private function obterTamanoDatabase(){
    $sgbd = $this->obterTipoSGBD();
    $query = '';
    if ($sgbd == 'MySql') {
      $query = "SELECT table_schema, SUM(data_length + index_length) as tamanho FROM information_schema.TABLES WHERE table_schema = 'sei' GROUP BY table_schema";
    }
    $rs = BancoSEI::getInstance()->consultarSql($query);
    $tamanho = (count($rs) && isset($rs[0]['tamanho'])) ? $rs[0]['tamanho'] : 0;

    InfraDebug::getInstance()->gravar('SEI03 - Tamanho do SGBD: ' . $tamanho, InfraLog::$INFORMACAO);
    return $tamanho;
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
    $resultado['0MB - 1MB'] = (count($rs) && isset($rs[0]['quantidade'])) ? $rs[0]['quantidade'] : 0;

    # 1MB - 10MB
    $query = "SELECT count(*) as quantidade FROM anexo WHERE sin_ativo = 'S' AND tamanho >= 1000 AND tamanho < 10000";
    $rs = BancoSEI::getInstance()->consultarSql($query);
    $resultado['1MB - 10MB'] = (count($rs) && isset($rs[0]['quantidade'])) ? $rs[0]['quantidade'] : 0;

    # 10MB - 100MB
    $query = "SELECT count(*) as quantidade FROM anexo WHERE sin_ativo = 'S' AND tamanho >= 10000 AND tamanho < 100000";
    $rs = BancoSEI::getInstance()->consultarSql($query);
    $resultado['10MB - 100MB'] = (count($rs) && isset($rs[0]['quantidade'])) ? $rs[0]['quantidade'] : 0;

    # > 100MB
    $query = "SELECT count(*) as quantidade FROM anexo WHERE sin_ativo = 'S' AND tamanho >= 100000";
    $rs = BancoSEI::getInstance()->consultarSql($query);
    $resultado['Maior que 100MB'] = (count($rs) && isset($rs[0]['quantidade'])) ? $rs[0]['quantidade'] : 0;

    InfraDebug::getInstance()->gravar('SEI11 - Tamanho dos documentos externos: ' . json_encode($resultado), InfraLog::$INFORMACAO);
    return $resultado;
  }

}
?>
