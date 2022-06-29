<?php

use function ptk\fs\join_path;
use function ptk\tabular\read_csv;
use function ptk\tabular\write_csv;

require 'vendor/autoload.php';
require 'config.php';
require 'transform.php';

//pega a primeira linha
$handle = fopen($msc_file, 'r');
$header = explode(';', trim(fgets($handle)));
fclose($handle);
$entidade = $header[0];
$competencia = $header[1];
$periodo = explode('-', $competencia);
$ultimo_dia = [
    1 => 31,
    2 => 28,
    3 => 31,
    4 => 30,
    5 => 31,
    6 => 30,
    7 => 31,
    8 => 31,
    9 => 30,
    10 => 31,
    11 => 30,
    12 => 31,
    13 => 31
];
$encerramento = false;
if($periodo[1] == 13){
    $periodo[1] = 12;
    $encerramento = true;
}
$competencia = new DateTime();
$competencia->setDate($periodo[0], $periodo[1], $ultimo_dia[(int) $periodo[1]]);

//carrega a msc
$handle = fopen($msc_file, 'r');
$msc = read_csv($handle, ';', true, 1);
fclose($handle);
//print_r($msc);
//balancete contábil
echo "Criando o Balancete Contábil:", PHP_EOL;
//cria a estrutura de dados convertidos
$bal_cont_data = [];

//inicia loop pelos dados

foreach ($msc as $id => $row) {
    $bal_cont_data[$id]['Entidade'] = $entidade;
    $bal_cont_data[$id]['Competencia'] = $competencia->format('d/m/Y');
    $bal_cont_data[$id]['ContaContabil'] = transf_cc($row['ContaContabil']);
    //$bal_cont_data[$id]['Valor'] = $row['Valor'];
    //$bal_cont_data[$id]['ValorF'] = number_format($row['Valor'], 2, ',', '.');
    $bal_cont_data[$id]['Valor'] = number_format($row['Valor'], 2, ',', '.');
    $bal_cont_data[$id]['TipoValor'] = $row['TipoValor'];
    $bal_cont_data[$id]['NaturezaValor'] = $row['NaturezaValor'];

    //inicialização das informações complementares
    $bal_cont_data[$id]['PoderOrgao'] = null;
    $bal_cont_data[$id]['FinanceiroPermanente'] = null;
    $bal_cont_data[$id]['DividaConsolidada'] = null;
    $bal_cont_data[$id]['FonteRecurso'] = null;
    $bal_cont_data[$id]['ComplementoFonteRecurso'] = null;
    $bal_cont_data[$id]['NaturezaReceita'] = null;
    $bal_cont_data[$id]['NaturezaDespesa'] = null;
    $bal_cont_data[$id]['FuncaoSubfuncao'] = null;
    $bal_cont_data[$id]['AnoInscricaoRestosAPagar'] = null;
    $bal_cont_data[$id]['DespesasMDEeASPS'] = null;

    //identifica as informações complementares
    for ($i = 1; $i <= 6; $i++) {
        $TipoIC = $row["TipoInformacaoComplementar$i"];
        $ValorIC = $row["InformacaoComplementar$i"];
//        var_dump($ValorIC);exit();
        switch ($TipoIC) {
            case 'PO':
                $destiny = 'PoderOrgao';
                break;
            case 'FP':
                $destiny = 'FinanceiroPermanente';
                break;
            case 'DC':
                $destiny = 'DividaConsolidada';
                break;
            case 'FR':
                $destiny = 'FonteRecurso';
                $ValorIC = transf_fr($ValorIC);
                break;
            case 'CF':
                $destiny = 'ComplementoFonteRecurso';
                break;
            case 'NR':
                $destiny = 'NaturezaReceita';
                $ValorIC = transf_nro($ValorIC);
                break;
            case 'ND':
                $destiny = 'NaturezaDespesa';
                $ValorIC = transf_ndo($ValorIC);
                break;
            case 'FS':
                $destiny = 'FuncaoSubfuncao';
                $ValorIC = transf_fs($ValorIC);
                break;
            case 'AI':
                $destiny = 'AnoInscricaoRestosAPagar';
                break;
            case 'ES':
                $destiny = 'DespesasMDEeASPS';
                break;
            default :
                $destiny = false;
                break;
        }
//        var_dump($destiny);exit();

        if($destiny){
            $bal_cont_data[$id][$destiny] = $ValorIC;
        }
//        print_r($output);exit();
    }
}

//print_r($output);
//salva o conteúdo
//$handle = fopen(join_path($output_dir, $bal_cont_file), 'w');
//$bal_cont_file = $entidade.'_'.$competencia->format('Y-m').'.csv';
$bal_cont_file = $competencia->format('Y-m').'.csv';
if($encerramento){
    $bal_cont_file = $competencia->format('Y').'-13.csv';
}
$handle = fopen(join_path($output_dir, $bal_cont_file), 'w');
write_csv($handle, $bal_cont_data, ';', true);
fclose($handle);

echo "\tlinhas criadas: ", sizeof($bal_cont_data), PHP_EOL;

/*//balancete da receita
echo "Criando o Balancete da Receita:", PHP_EOL;

$bal_rec_data = [];

//loop sobre os dados contábeis
foreach ($bal_cont_data as $id => $row) {
    if (strlen($row['NaturezaReceita']) === 0) {
        continue;
    }
    
    $nro = $row['NaturezaReceita'];
    
    if(!key_exists($nro, $bal_rec_data)){
        $bal_rec_data[$nro] = [
            'PrevisaoInicialBruta' => 0,
            'PrevisaoInicialDeducoes' => 0,
            'AlteracoesBruta' => 0,
            'AlteracoesDeducoes' => 0,
            'ArrecadadoBruto' => 0,
            'ArrecadadoDeducoes' => 0
        ];
    }
    
    //previsão inicial
    if($row['ContaContabil'] === '5.2.1.1.1.00.00'){//previsão inicial bruta
        if($row['TipoValor'] === 'ending_balance'){
            $bal_rec_data[$nro]['PrevisaoInicialBruta'] += $row['Valor'];
        }
    }
    if(//deduções da previsão inicial
            $row['ContaContabil'] === '5.2.1.1.2.01.01'
            || $row['ContaContabil'] === '5.2.1.1.2.01.02'
            || $row['ContaContabil'] === '5.2.1.1.2.99.00'
        ){
        if($row['TipoValor'] === 'ending_balance'){
            $bal_rec_data[$nro]['PrevisaoInicialDeducoes'] += $row['Valor'];
        }
    }
    
    //alteração da previsão
    if(
            $row['ContaContabil'] === '5.2.1.2.1.01.00'
            || $row['ContaContabil'] === '5.2.1.2.1.02.00'
        ){//reestimativa
        if($row['TipoValor'] === 'ending_balance'){
            $bal_rec_data[$nro]['AlteracoesBruta'] += $row['Valor'];
        }
    }
    if(//alteração das deduções
            $row['ContaContabil'] === '5.2.1.2.1.03.01'
            || $row['ContaContabil'] === '5.2.1.2.1.03.02'
            || $row['ContaContabil'] === '5.2.1.2.1.04.00'
            || $row['ContaContabil'] === '5.2.1.2.1.99.00'
        ){
        if($row['TipoValor'] === 'ending_balance'){
            $bal_rec_data[$nro]['AlteracoesDeducoes'] += $row['Valor'];
        }
    }
    
    //arrecadação
    if(
            $row['ContaContabil'] === '6.2.1.2.0.00.00'
        ){
        if($row['TipoValor'] === 'ending_balance'){
            $bal_rec_data[$nro]['ArrecadadoBruto'] += $row['Valor'];
        }
    }
    
    if(
            $row['ContaContabil'] === '6.2.1.3.1.01.00'
            || $row['ContaContabil'] === '6.2.1.3.1.02.00'
            || $row['ContaContabil'] === '6.2.1.3.2.00.00'
            || $row['ContaContabil'] === '6.2.1.3.9.00.00'
        ){
        if($row['TipoValor'] === 'ending_balance'){
            $bal_rec_data[$nro]['ArrecadadoDeducoes'] += $row['Valor'];
        }
    }
    
}

//transpões os dados para o formato tabular
$output = [];
foreach ($bal_rec_data as $nro => $data){
    $output[] = [
        'NaturezaReceita' => $nro,
        'PrevisaoInicialBruta' => number_format($data['PrevisaoInicialBruta'], 2, ',', '.'),
        'PrevisaoInicialDeducoes' => number_format($data['PrevisaoInicialDeducoes'], 2, ',', '.'),
        'PrevisaoInicialLiquida' => number_format($data['PrevisaoInicialBruta'] - $data['PrevisaoInicialDeducoes'], 2, ',', '.'),
        'AlteracoesBruta' => number_format($data['AlteracoesBruta'], 2, ',', '.'),
        'AlteracoesDeducoes' => number_format($data['AlteracoesDeducoes'], 2, ',', '.'),
        'AlteracoesLiquida' => number_format($data['AlteracoesBruta'] - $data['AlteracoesDeducoes'], 2, ',', '.'),
        'PrevisaoAtualizadaBruta' => number_format($data['PrevisaoInicialBruta']+$data['AlteracoesBruta'], 2, ',', '.'),
        'PrevisaoAtualizadaDeducoes' => number_format($data['PrevisaoInicialDeducoes']+$data['AlteracoesDeducoes'], 2, ',', '.'),
        'PrevisaoAtualizadaLiquida' => number_format($data['PrevisaoInicialBruta'] - $data['PrevisaoInicialDeducoes'] + $data['AlteracoesBruta'] - $data['AlteracoesDeducoes'], 2, ',', '.'),
        'ArrecadadoBruto' => number_format($data['ArrecadadoBruto'], 2, ',', '.'),
        'ArrecadadoDeducoes' => number_format($data['ArrecadadoDeducoes'], 2, ',', '.'),
        'ArrecadadoLiquido' => number_format($data['ArrecadadoBruto'] - $data['ArrecadadoDeducoes'], 2, ',', '.'),
    ];
}

//salva os dados
$handle = fopen(join_path($output_dir, $bal_rec_file), 'w');
write_csv($handle, $output, ';', true);
fclose($handle);
echo "\tlinhas criadas: ", sizeof($output), PHP_EOL;

//balancete da despesa
echo "Criando o Balancete da Despesa:", PHP_EOL;

$bal_desp_data = [];

foreach ($bal_cont_data as $row){
    if (strlen($row['NaturezaDespesa']) === 0) {
        continue;
    }
    
    $ndo = $row['NaturezaDespesa'];
    
    if(!key_exists($ndo, $bal_desp_data)){
        $bal_desp_data[$ndo] = [
            'DotacaoInicial' => 0,
            'CreditoSuplementar' => 0,
            'CreditoEspecialAberto' => 0,
            'CreditoEspecialReaberto' => 0,
            'CreditoExtraordinarioAberto' => 0,
            'CreditoExtraordinarioReaberto' => 0,
            'ArrecadadoDeducoes' => 0
        ];
    }
}

//salva os dados
$handle = fopen(join_path($output_dir, $bal_desp_file), 'w');
write_csv($handle, $output, ';', true);
fclose($handle);
echo "\tlinhas criadas: ", sizeof($output), PHP_EOL;*/