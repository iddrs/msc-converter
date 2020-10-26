<?php

use function ptk\fs\join_path;
use function ptk\tabular\read_csv;
use function ptk\tabular\write_csv;

require 'config.php';
require 'vendor/autoload.php';

//carrega a msc
$handle = fopen($msc_file, 'r');
$msc = read_csv($handle, ';', true, 1);
fclose($handle);
//print_r($msc);

//cria a estrutura de dados convertidos
$output = [];

//inicia loop pelos dados

foreach ($msc as $id => $row){
    $output[$id]['ContaContabil'] = $row['ContaContabil'];
    $output[$id]['Valor'] = $row['Valor'];
    $output[$id]['ValorF'] = number_format($row['Valor'], 2, ',', '.');
    $output[$id]['TipoValor'] = $row['TipoValor'];
    $output[$id]['NaturezaValor'] = $row['NaturezaValor'];
    
    //inicialização das informações complementares
    $output[$id]['PoderOrgao'] = null;
    $output[$id]['FinanceiroPermanente'] = null;
    $output[$id]['DividaConsolidada'] = null;
    $output[$id]['FonteRecurso'] = null;
    $output[$id]['ComplementoFonteRecurso'] = null;
    $output[$id]['NaturezaReceita'] = null;
    $output[$id]['NaturezaDespesa'] = null;
    $output[$id]['FuncaoSubfuncao'] = null;
    $output[$id]['AnoInscricaoRestosAPagar'] = null;
    $output[$id]['DespesasMDEeASPS'] = null;
    
    //identifica as informações complementares
    for($i = 1; $i <= 7; $i++){
        $colname = "TipoInformacaoComplementar$i";
        $TipoIC = $row[$colname];
        $ValorIC = $row["InformacaoComplementar$i"];
//        var_dump($ValorIC);exit();
        switch ($TipoIC){
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
                break;
            case 'CF':
                $destiny = 'ComplementoFonteRecurso';
                break;
            case 'NR':
                $destiny = 'NaturezaReceita';
                break;
            case 'ND':
                $destiny = 'NaturezaDespesa';
                break;
            case 'FS':
                $destiny = 'FuncaoSubfuncao';
                break;
            case 'AI':
                $destiny = 'AnoInscricaoRestosAPagar';
                break;
            case 'ES':
                $destiny = 'DespesasMDEeASPS';
                break;
        }
//        var_dump($destiny);exit();

        $output[$id][$destiny] = $ValorIC;
//        print_r($output);exit();
    }
}
//print_r($output);

//salva o conteúdo
$handle = fopen(join_path($output_dir, $bal_cont_file), 'w');
write_csv($handle, $output, ';', true);
fclose($handle);