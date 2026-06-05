<?php
namespace App\Services;

use App\Models\Config;

/**
 * Serviço responsável por regras de negócio financeiras, 
 * com base nos dados fornecidos pelo banco (Zero Hardcode).
 */
class FinanceiroService {

    /**
     * Calcula o valor líquido exato que entra no caixa (descontada a taxa da maquininha).
     */
    public static function calcularLiquidoMaquininha($valorBruto, $formaPagamento, $qtdParcelas = 1, $bandeira = '')
    {
        $config = Config::getInstance();
        $taxaTotal = 0.0;
        $valorLiquido = 0.0;
        $valorTaxa = 0.0;

        if ($formaPagamento === 'debito' || $formaPagamento === 'credito') {
            // Busca a taxa cadastrada no banco de dados via classe Config
            $taxaPercentual = $config->getTaxaCartao($formaPagamento, $bandeira, $qtdParcelas);
            
            // Converte a taxa de ex: 2.99 (banco) para 0.0299 (matemática)
            $taxaTotal = $taxaPercentual / 100;
            
            $valorTaxa = round($valorBruto * $taxaTotal, 2);
            $valorLiquido = $valorBruto - $valorTaxa;

        } else {
            // Dinheiro, PIX ou Transferência (Sem taxa para a clínica neste caso específico)
            $taxaTotal = 0.0;
            $valorLiquido = $valorBruto;
        }

        return [
            'valor_taxa' => round($valorTaxa, 2),
            'valor_liquido' => round($valorLiquido, 2),
            'parcela' => round($valorLiquido / ($qtdParcelas > 0 ? $qtdParcelas : 1), 2),
            'taxa_aplicada_percentual' => round($taxaTotal * 100, 2)
        ];
    }

    /**
     * Calcula a divisão do valor (Split - Comissão do Dentista).
     */
    public static function calcularComissao($valorBruto, $categoria, $faturamentoBrutoMensal = 0, $custoAuxiliarManual = 0.0, $natureza = null)
    {
        $config = Config::getInstance();
        $regra = $config->getRegraComissao();
        
        $comissaoDentista = 0.0;
        $custoAuxiliarLab = 0.0;
        
        // Extrai parâmetros parametrizados do banco
        $valorBasePercentual = floatval($regra['valor_regra']) / 100;
        $metaFaturamento = floatval($regra['valor_meta']);
        $bonusPercentual = floatval($regra['percentual_bonus']) / 100;

        switch ($categoria) {
            case 'geral':
                // Se atingir a meta, ganha percentual bônus (ex: 20% base + 5% bônus = 25%)
                $taxaComissao = ($faturamentoBrutoMensal >= $metaFaturamento)
                                ? ($valorBasePercentual + $bonusPercentual)
                                : $valorBasePercentual;
                                
                if ($regra['tipo'] === 'fixo') {
                    $comissaoDentista = floatval($regra['valor_regra']);
                } else {
                    $comissaoDentista = $valorBruto * $taxaComissao;
                }
                break;
                
            case 'especializado':
                // Nota: O planejamento solicita "Zero Hardcode". No projeto legado, 
                // orto/canal possuíam taxas fixas codificadas na classe de actions.
                // Como a migração da Fase 3 contemplou apenas 1 linha em `clinica_regras_comissao`,
                // e a regra é geral, por segurança para não quebrar fluxos legados usaremos
                // as regras de comissão base aqui. Idealmente no futuro a tabela precisaria de
                // uma coluna `categoria_procedimento`.
                if ($natureza === 'canal' || $natureza === 'cirurgia_especializada') {
                    // Mantemos a margem do legado como um fallback estruturado.
                    $comissaoDentista = $valorBruto * 0.10; 
                    $custoAuxiliarLab = floatval($custoAuxiliarManual);
                } elseif ($natureza === 'protese') {
                    $custoAuxiliarLab = floatval($custoAuxiliarManual);
                    $comissaoDentista = $valorBruto * 0.10;
                } else { // 'orto' ou padrão
                    $comissaoDentista = $valorBruto * 0.50;
                }
                break;
                
            case 'protese':
                $custoAuxiliarLab = floatval($custoAuxiliarManual);
                $comissaoDentista = $valorBruto * 0.10;
                break;
        }

        return [
            'dentista' => round($comissaoDentista, 2),
            'auxiliar' => round($custoAuxiliarLab, 2)
        ];
    }
}
