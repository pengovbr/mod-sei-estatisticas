<?
require_once dirname(__FILE__) . '/../../web/SEI.php';

try {

  class MdEstatisticasVersaoScriptRN extends InfraScriptVersao
  {
    const PARAMETRO_VERSAO_MODULO = 'VERSAO_MODULO_ESTATISTICAS';
    const NOME_MODULO = 'Módulo de Estatísticas do SEI';

    public function __construct()
    {
      parent::__construct();
      ini_set('max_execution_time', '0');
      ini_set('memory_limit', '-1');

      SessaoSEI::getInstance(false);

      InfraDebug::getInstance()->setBolLigado(false);
      InfraDebug::getInstance()->setBolDebugInfra(false);
      InfraDebug::getInstance()->setBolEcho(true);
      InfraDebug::getInstance()->limpar();
    }

    protected function inicializarObjInfraIBanco()
    {
      return BancoSEI::getInstance();
    }

    protected function verificarVersaoInstaladaControlado()
    {
      $objInfraParametroDTO = new InfraParametroDTO();
      $objInfraParametroDTO->setStrNome(MdEstatisticasVersaoScriptRN::PARAMETRO_VERSAO_MODULO);
      $objInfraParametroBD = new InfraParametroBD(BancoSEI::getInstance());
      if ($objInfraParametroBD->contar($objInfraParametroDTO) == 0) {
        $objInfraParametroDTO->setStrValor('0.0.0');
        $objInfraParametroBD->cadastrar($objInfraParametroDTO);
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
      $this->logar('CRIANDO AGENDAMENTO DE COLETA DE INDICADORES.');

      $objInfraAgendamentoTarefaDTO = new InfraAgendamentoTarefaDTO();
      $objInfraAgendamentoTarefaDTO->setStrComando('MdEstatisticasAgendamentoRN::coletarIndicadores');
      $objInfraAgendamentoTarefaBD = new InfraAgendamentoTarefaBD(BancoSEI::getInstance());

      if ($objInfraAgendamentoTarefaBD->contar($objInfraAgendamentoTarefaDTO) == 0) {
        $objInfraAgendamentoTarefaDTO->setStrDescricao('Coleta de Indicadores');
        $objInfraAgendamentoTarefaDTO->setStrStaPeriodicidadeExecucao('D');
        $objInfraAgendamentoTarefaDTO->setStrPeriodicidadeComplemento('05:00');
        $objInfraAgendamentoTarefaDTO->setStrSinSucesso('N');
        $objInfraAgendamentoTarefaDTO->setStrSinAtivo('S');
        $objInfraAgendamentoTarefaBD->cadastrar($objInfraAgendamentoTarefaDTO);
        $this->logar('Agendamento MdEstatisticasAgendamentoRN::coletarIndicadores criado.');
      } else {
        $this->logar('Agendamento MdEstatisticasAgendamentoRN::coletarIndicadores já existente.');
      }

      $this->logar('VERSÃO 2.0.1 atualizada.');
    }
  }

  session_start();
  SessaoSEI::getInstance(false);
  BancoSEI::getInstance()->setBolScript(true);

  $objVersaoSeiRN = new MdEstatisticasVersaoScriptRN();
  $objVersaoSeiRN->verificarVersaoInstalada();
  $objVersaoSeiRN->setStrNome(MdEstatisticasVersaoScriptRN::NOME_MODULO);
  $objVersaoSeiRN->setStrVersaoAtual(MdEstatisticas::VERSAO_MODULO);
  $objVersaoSeiRN->setStrParametroVersao(MdEstatisticasVersaoScriptRN::PARAMETRO_VERSAO_MODULO);
  $objVersaoSeiRN->setArrVersoes(
    array(
      '0.0.0' => 'versao_0_0_0',
      '1.0.0' => 'versao_1_0_0',
      '2.0.0' => 'versao_2_0_0',
      '2.0.1' => 'versao_2_0_1',
    )
  );

  $objVersaoSeiRN->setStrVersaoInfra('1.595.1');
  $objVersaoSeiRN->setBolMySql(true);
  $objVersaoSeiRN->setBolOracle(true);
  $objVersaoSeiRN->setBolSqlServer(true);
  $objVersaoSeiRN->setBolPostgreSql(true);
  $objVersaoSeiRN->setBolErroVersaoInexistente(true);
  $objVersaoSeiRN->atualizarVersao();
} catch (Exception $e) {
  echo (InfraException::inspecionar($e));
  try {
    LogSEI::getInstance()->gravar(InfraException::inspecionar($e));
  } catch (Exception $e) {
  }
  exit(1);
}
