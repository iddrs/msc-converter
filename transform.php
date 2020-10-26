<?php

function transf_cc(string $cc): string {
    return $cc[0].'.'.$cc[1].'.'.$cc[2].'.'.$cc[3].'.'.$cc[4].'.'.$cc[5].$cc[6].'.'.$cc[7].$cc[8];
}

function transf_nro(string $nro): string {
    return $nro[0].'.'.$nro[1].'.'.$nro[2].'.'.$nro[3].'.'.$nro[4].$nro[5].'.'.$nro[6].'.'.$nro[7];
}

function transf_ndo(string $ndo): string {
    return $ndo[0].'.'.$ndo[1].'.'.$ndo[2].$ndo[3].'.'.$ndo[4].$ndo[5].'.'.$ndo[6].$ndo[4];
}

function transf_fs(string $fs): string {
    return $fs[0].$fs[1].'.'.$fs[2].$fs[3].$fs[4];
}

function transf_fr(string $fr): string {
    return $fr[0].'.'.$fr[1].$fr[2].$fr[3].'.'.$fr[4].$fr[5].$fr[6].$fr[7];
}