<?
require_once dirname(__FILE__) . '/../../../SEI.php';

class MdEstatisticasAgendamentoRN extends InfraRN
{

    protected function inicializarObjInfraIBanco() {
        return BancoSEI::getInstance();
    }

    public function coletarIndicadores() {
        InfraDebug::getInstance()->setBolLigado(true);
        InfraDebug::getInstance()->setBolDebugInfra(false);
        InfraDebug::getInstance()->setBolEcho(false);
        InfraDebug::getInstance()->limpar();

        try {
            $coletor = new MdEstatisticasColetarRN();
            $indicadores = $coletor->coletarIndicadores();
            InfraDebug::getInstance()->gravar('JSON: ' . json_encode($indicadores), InfraLog::$INFORMACAO);

            $enviar = new MdEstatisticasEnviarRN();

            $saida = $enviar->enviarIndicadores($indicadores);
            InfraDebug::getInstance()->gravar('Retorno: ' . json_encode($saida), InfraLog::$INFORMACAO);

            $id = $saida['id'];

            if ($id) {
                $data = $enviar->obterUltimoAcesso();
                InfraDebug::getInstance()->gravar('Data: ' . $data, InfraLog::$INFORMACAO);

                $acessos = $coletor->obterAcessosUsuarios($data);
                $enviar->enviarAcessos($acessos, $id);

                $velocidades = $coletor->obterVelocidadePorCidade();
                $enviar->enviarVelocidades($velocidades, $id);

                $sistemasOperacionaisUsuarios = $coletor->obterSistemasOperacionaisUsuarios();
                $enviar->enviarSistemasUsuarios($sistemasOperacionaisUsuarios, $id);

                $navegadores = $coletor->obterNavegadores();
                $enviar->enviarNavegadores($navegadores, $id);

                $logs = $coletor->obterQuantidadeLogErro();
                $enviar->enviarLogsErro($logs, $id);
                
                $recursos = $coletor->obterQuantidadeRecursos();
                $enviar->enviarRecursos($recursos, $id);
            }

            LogSEI::getInstance()->gravar(InfraDebug::getInstance()->getStrDebug(), InfraLog::$INFORMACAO);
        } catch (Exception $e) {
            InfraDebug::getInstance()->setBolLigado(false);
            InfraDebug::getInstance()->setBolDebugInfra(false);
            InfraDebug::getInstance()->setBolEcho(false);
            throw new InfraException('Erro processando estatísticas do sistema.', $e);
        }
    }
}
?>
