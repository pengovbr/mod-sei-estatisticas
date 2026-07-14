<?php
try {

  require_once dirname(__FILE__) . '/../../../SEI.php';

  session_start();

  SessaoSEI::getInstance(false);
  function color($text, $colorCode) {
      return "\033[" . $colorCode . "m" . utf8_encode($text) . "\033[0m";
  }

  // Título
  echo color("============================================\n", "34"); // azul
  echo color("   CRIACAO DO AGENDAMENTO DE ESTATISTICAS\n", "32"); // verde
  echo color("============================================\n\n", "34");

  InfraDebug::getInstance()->setBolLigado(false);
  InfraDebug::getInstance()->setBolDebugInfra(false);
  InfraDebug::getInstance()->setBolEcho(true);
  InfraDebug::getInstance()->limpar();

  $objBanco = BancoSEI::getInstance();
  $objBanco->abrirConexao();
  $objBanco->abrirTransacao();

  // Instancia InfraMetaBD passando o objeto de banco
  $objMetaBD = new InfraMetaBD($objBanco);
    
  // Instancia InfraSequencia passando o objeto de banco
  $objInfraSequencia = new InfraSequencia($objBanco);

  InfraDebug::getInstance()->setBolDebugInfra(true);

  InfraDebug::getInstance()->gravar('INÍCIO');

  // Verifica se já existe um agendamento com o comando específico
  $sqlVerificaAgendamento = "SELECT COUNT(*) AS total FROM infra_agendamento_tarefa WHERE comando = 'MdEstatisticasAgendamentoRN::coletarIndicadores'";
  $arrVerifica = BancoSEI::getInstance()->consultarSql($sqlVerificaAgendamento);

  if (isset($arrVerifica[0]['total']) && $arrVerifica[0]['total'] == 0) {
    // Calcula o próximo id disponível para infra_agendamento_tarefa
    $sqlMaxId = "SELECT MAX(id_infra_agendamento_tarefa) AS max_id FROM infra_agendamento_tarefa";
    $arrResultado = BancoSEI::getInstance()->consultarSql($sqlMaxId);
    $proxIdAgendamentoTarefa = isset($arrResultado[0]['max_id']) ? ((int)$arrResultado[0]['max_id'] + 1) : 1;

    $sql = "INSERT INTO infra_agendamento_tarefa (id_infra_agendamento_tarefa, descricao, comando, sta_periodicidade_execucao, periodicidade_complemento, sin_sucesso, sin_ativo)
    VALUES (".$proxIdAgendamentoTarefa.", 'Coleta de Indicadores', 'MdEstatisticasAgendamentoRN::coletarIndicadores', 'D', '05:00', 'N', 'S')";
    BancoSEI::getInstance()->executarSql($sql);
  }

  BancoSEI::getInstance()->confirmarTransacao();
  BancoSEI::getInstance()->fecharConexao();

  InfraDebug::getInstance()->gravar('FIM');

}catch(Exception $e){

  try {
    BancoSEI::getInstance()->cancelarTransacao();
  }catch(Exception $e){}

  try {
    BancoSEI::getInstance()->fecharConexao();
  }catch(Exception $e){}

  echo(InfraException::inspecionar($e));
  try{LogSEI::getInstance()->gravar(InfraException::inspecionar($e));  }catch (Exception $e){}
}
?>