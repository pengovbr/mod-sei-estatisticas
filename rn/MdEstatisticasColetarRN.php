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
        'seiVersao' => $this->obterVersaoSEI(),
        'phpVersao' => $this->obterVersaoPHP(),
        'protocolo' => $this->obterProtocolo(),
        'quantidadeUnidades' => $this->obterQuantidadeUnidades(),
        'quantidadeProcedimentos' => $this->obterQuantidadeProcessosAdministrativos(),
        'quantidadeUsuarios' => $this->obterQuantidadeUsuarios(),
        'navegadores' => $this->obterNavegadores(),
        'modulos' => $this->obterPlugins(),
        'tamanhoFilesystem' => $this->obterTamanhoFileSystem(),
        'bancoSEI' => $this->obterTipoSGBD(),
        'quantidadeDocumentosInternos' => $this->obterQuantidadeDocumentosInternos(),
        'quantidadeDocumentosExternos' => $this->obterQuantidadeDocumentosExternos(),
        'quantidadeDocumentosExternosPorExtensao' => $this->obterQuantidadeDocumentosExternosPorExtensao(),
        'estrategiaCessao' => $this->obterEstrategiaCessao(),
        'versaoMemcached' => $this->obterVersaoMemcached()
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

}
?>
