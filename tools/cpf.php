function ckcpf(\$cpf) {
	if(strlen(\$cpf) != 11) {
		return false;
	}

	\$bak = \$cpf;

	\$sum = 0;
	for(\$i = 10; \$i > 1; \$i--) {
		\$sum = \$sum + (\$i * \$cpf{10 - \$i});
	}
	if((\$cpf{9} = 11 - (\$sum % 11)) > 9) {
		\$cpf{9} = 0;
	}

	\$sum = 0;
	for(\$i = 11; \$i > 1; \$i--) {
		\$sum = \$sum + (\$i * \$cpf{11 - \$i});
	}
	if((\$cpf{10} = 11 - (\$sum % 11)) > 9) {
		\$cpf{10} = 0;
	}

	return (\$cpf == \$bak);
}
