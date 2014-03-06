CREATE TABLE state (
	point VARCHAR( 255 ) NOT NULL ,
	state VARCHAR( 255 ) NOT NULL ,
	epoc INT NOT NULL DEFAULT 0 ,
	UNIQUE (
		point
	)
);
