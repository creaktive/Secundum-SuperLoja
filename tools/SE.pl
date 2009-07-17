#!/usr/bin/perl -w
use strict;

my %key;
while (<>) {
	chomp;
	s/^\s+//;
	s/#.*//;
	s/\s+$//;
	next unless $_;

	my ($kw, $url, $key) = split (/\s+/, $_, 3);
	next if not defined $key or not ($key =~ /=$/);
	++$key{$key};
}

foreach my $key (sort { $key{$b} <=> $key{$a} } keys %key) {
	printf "%5d '%s',\n", $key{$key}, $key;
}

exit;
