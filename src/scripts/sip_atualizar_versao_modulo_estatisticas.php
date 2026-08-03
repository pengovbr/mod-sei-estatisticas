<?php
define('VERSAO_MODULO_ESTATISTICAS', '2.0.1');

require_once dirname(__FILE__) . '/../../web/Sip.php';

class VersaoSipRN extends InfraScriptVersao
{
  const PARAMETRO_VERSAO_MODULO = 'VERSAO_MODULO_ESTATISTICAS';
  const NOME_MODULO = 'Módulo de Estatísticas - SIP';

  public function __construct()
  {
    parent::__construct();
  }

  protected function inicializarObjInfraIBanco()
  {
    return BancoSip::getInstance();
  }

  protected function verificarVersaoInstaladaControlado()
  {
    $objInfraParametroDTO = new InfraParametroDTO();
    $objInfraParametroDTO->setStrNome(VersaoSipRN::PARAMETRO_VERSAO_MODULO);
    $objInfraParametroDB = new InfraParametroBD(BancoSip::getInstance());
    if ($objInfraParametroDB->contar($objInfraParametroDTO) == 0) {
      $objInfraParametroDTO->setStrValor('0.0.0');
      $objInfraParametroDB->cadastrar($objInfraParametroDTO);
    }
  }

  public function versao_0_0_0($strVersaoAtual)
  {
    $this->logar('VERSÃO 0.0.0 atualizada.');
  }

  public function versao_1_0_0($strVersaoAtual)
  {
    $this->logar('VERSÃO 1.0.0 atualizada.');
  }

  public function versao_2_0_0($strVersaoAtual)
  {
    $this->logar('VERSÃO 2.0.0 atualizada.');
  }

  public function versao_2_0_1($strVersaoAtual)
  {
    $this->logar('VERSÃO 2.0.1 atualizada.');
  }
}

try {
  session_start();

  SessaoSip::getInstance(false);
  BancoSip::getInstance()->setBolScript(true);

  $objVersaoSipRN = new VersaoSipRN();
  $objVersaoSipRN->verificarVersaoInstalada();
  $objVersaoSipRN->setStrNome(VersaoSipRN::NOME_MODULO);
  $objVersaoSipRN->setStrVersaoAtual(VERSAO_MODULO_ESTATISTICAS);
  $objVersaoSipRN->setStrParametroVersao(VersaoSipRN::PARAMETRO_VERSAO_MODULO);
  $objVersaoSipRN->setArrVersoes(
    array(
      '0.0.0' => 'versao_0_0_0',
      '1.0.0' => 'versao_1_0_0',
      '2.0.0' => 'versao_2_0_0',
      '2.0.1' => 'versao_2_0_1',
    )
  );

  $objVersaoSipRN->setStrVersaoInfra('1.595.1');
  $objVersaoSipRN->setBolMySql(true);
  $objVersaoSipRN->setBolOracle(true);
  $objVersaoSipRN->setBolSqlServer(true);
  $objVersaoSipRN->setBolPostgreSql(true);
  $objVersaoSipRN->setBolErroVersaoInexistente(true);
  $objVersaoSipRN->atualizarVersao();
} catch (Exception $e) {
  echo (InfraException::inspecionar($e));
  try {
    LogSip::getInstance()->gravar(InfraException::inspecionar($e));
  } catch (Exception $e) {
  }
  exit(1);
}
