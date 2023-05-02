<?php
function count_percentages($index,$amount,$percentage) {
	if ($amount>10) {
		if ( ( $index % ($amount/10) === 0 || $index+1 === $amount ) && $index!=0) {
			$percentage++;
			echo "[";
			for ($percent_i = 0; $percent_i < 10; $percent_i++) {
				if ($percent_i < $percentage) {
					echo "*";
				} else {
					echo "_";
				}
			}
			echo "]<br>";
		}
	} elseif ($index+1 === $amount) {
		echo "[**********]<br>";
	}
	return $percentage;
}