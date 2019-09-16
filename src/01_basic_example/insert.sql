set @KEY:=:key;

INSERT
INTO `rester-sql`.`example`
(`key`, `value`)
VALUES (@KEY, :value)

