<?php

function getStockStatus($qty, $lower, $upper){

    if($qty == 0){
        return "Out of Stock";
    }

    if($qty < $lower){
        return "Low";
    }

    if($qty <= $lower + 5){
        return "Medium";
    }

    if($qty > $upper){
        return "Overstocked";
    }

    return "Adequate";
}