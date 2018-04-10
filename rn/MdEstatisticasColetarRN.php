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

      InfraDebug::getInstance()->setBolLigado(true);
      InfraDebug::getInstance()->setBolDebugInfra(false);
      InfraDebug::getInstance()->setBolEcho(false);
      InfraDebug::getInstance()->limpar();

      //IMPLEMENTA��O DA EXTRA��O DE INDICADORES
      //1) Criar o objeto DTO representativo dos indicadores
      $objIndicadoresDTO = new IndicadoresDTO();

      //2) Preencher cada indicador do sistema
      $objIndicadoresDTO->setStrVersaoSEI($this->obterVersaoSEI());
      $objIndicadoresDTO->setStrVersaoPHP($this->obterVersaoPHP());
      $objIndicadoresDTO->setNumTamanhoFileSystem($this->obterTamanhoFileSystem());
      $objIndicadoresDTO->setStrPlugins($this->obterPlugins());
      $objIndicadoresDTO->setNumQuantidadeUnidades($this->obterQuantidadeUnidades());
      $objIndicadoresDTO->setNumTamanhoDocumentosExternos($this->obterTamanhoTotalDocumentosExternos());
      $objIndicadoresDTO->setStrProtocolo($this->obterProtocolo());
      $objIndicadoresDTO->setNumQuantidadeProcedimentos($this->obterQuantidadeProcessosAdministrativos());
      $objIndicadoresDTO->setStrNavegadores($this->obterNavegadores());

      //...

      //3) Salvar indicador no banco de dados ????
      //

      //4) Enviar indicadores para webservice
      //

      LogSEI::getInstance()->gravar(InfraDebug::getInstance()->getStrDebug(),InfraLog::$INFORMACAO);
      return $objIndicadoresDTO;

    } catch(Exception $e) {
      InfraDebug::getInstance()->setBolLigado(false);
      InfraDebug::getInstance()->setBolDebugInfra(false);
      InfraDebug::getInstance()->setBolEcho(false);
      throw new InfraException('Erro processando estat�sticas do sistema.',$e);
    }
  }

  private function obterVersaoSEI(){
    InfraDebug::getInstance()->gravar('SEI01 - Vers�o SEI: ' . SEI_VERSAO, InfraLog::$INFORMACAO);
    return SEI_VERSAO;
  }

  private function obterVersaoPHP(){
    InfraDebug::getInstance()->gravar('SEI21 - Vers�o PHP: ' . phpversion(), InfraLog::$INFORMACAO);
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
    $resultado = json_encode($lista);

    InfraDebug::getInstance()->gravar('SEI03 - Plugins: ' . $resultado, InfraLog::$INFORMACAO);
    return $resultado;
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
        'quantidade' => $r['quantidade'],
        'navegador' => $r['identificacao'],
        'versao' => $r['versao']
      );
      array_push($lista, $result);
    }
    $resultado = json_encode($lista);

    InfraDebug::getInstance()->gravar('SEI13 - Quantidade de Navegadores: ' . $resultado, InfraLog::$INFORMACAO);
    return $quantidade;
  }

}
?>