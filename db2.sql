-- --------------------------------------------------------
-- 호스트:                          192.168.99.100
-- 서버 버전:                        10.3.12-MariaDB-1:10.3.12+maria~bionic - mariadb.org binary distribution
-- 서버 OS:                        debian-linux-gnu
-- HeidiSQL 버전:                  10.1.0.5464
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- 테이블 rester-sql.db2_table 구조 내보내기
CREATE TABLE IF NOT EXISTS `db2_table` (
  `no` int(11) NOT NULL AUTO_INCREMENT COMMENT '테이블키',
  `value` varchar(100) NOT NULL DEFAULT '0',
  PRIMARY KEY (`no`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='외부데이터베이스 호출 예제 테이블';

-- 테이블 데이터 rester-sql.db2_table:~0 rows (대략적) 내보내기
/*!40000 ALTER TABLE `db2_table` DISABLE KEYS */;
INSERT INTO `db2_table` (`no`, `value`) VALUES
	(1, 'db2 테이블 호출에 성공 하셨습니다.');
/*!40000 ALTER TABLE `db2_table` ENABLE KEYS */;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
