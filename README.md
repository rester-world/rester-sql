## 개요
RESTful를 기반으로 데이터베이스를 사용하는 프레임워크이다.

이외에 Redis를 사용한 Cache 기능과 인증 기능, 다중 데이터베이스 접근 기능 등을 제공한다.

자세한 내용은 아래 내용을 참조.

 - [Wiki 페이지 바로가기](https://github.com/rester-world/rester-sql/wiki)
 - [Rester-sql-demo 바로 가기](https://github.com/rester-world/rester-sql-demo)

## Docker

Rester-sql은 기본적으로 Docker를 기반으로 운영되도록 설계되었다.
 
Docker 이미지는 [Rester-docker](https://hub.docker.com/r/rester/rester-docker)에 Rester-sql의 프레임워크를 올려서 동작한다.

### Dockerfile
```
FROM rester/rester-docker
MAINTAINER Woong-Gi Jeon<jeon.wbbi@gmail.com>

RUN mkdir /var/www/cfg

ADD cfg /var/www/cfg
ADD src /var/www/html
ADD default.conf /etc/nginx/sites-available/default.conf

VOLUME ["/var/www/cfg"]
VOLUME ["/var/www/html/modules"]
```

### 버전
|  Nginx Version | PHP Version | Alpine Version |
|------|--------|--------|
| 1.14.0 | 7.2.4 | 3.7 |

### 링크
- [Rester-sql Dockerhub 바로가기](https://hub.docker.com/r/rester/rester-sql)

## 시작하기
 아래에서 도커를 사용한 기본적은 시작 방법에 대해서 설명한다.

### 이미지 생성
docker 이미지 빌드하기:
```
docker build --tag rester/rester-sql:latest .
```

### 컨테이너 생성
명령어로 컨테이너 생성:
```
docker run -d --restart always --name redis redis:latest
docker run -d --restart always --env MYSQL_RANDOM_ROOT_PASSWORD='true' --env MYSQL_DATABASE='rester-sql' --env MYSQL_USER='rester-sql' --env MYSQL_PASSWORD='rester-sql' --volume "$(pwd)/example_db.sql:/docker-entrypoint-initdb.d/dump.sql:ro" --name db mariadb:latest
docker run -d --restart always -p 80:80 --link db:db.rester.kr --link redis:cache.rester.kr --volume "$(pwd)/src/modules:/var/www/html/modules:ro" --volume "$(pwd)/cfg:/var/www/cfg:ro" --name rester-sql rester/rester-sql:latest
```

docker-compose.yml으로 컨테이너 실행:
```
docker-compose up -d
```

### 동작 확인
컨테이너 확인:
```
docker ps
```
```
CONTAINER ID        IMAGE                      COMMAND                  CREATED             STATUS              PORTS                                   NAMES
30105f0ed1e9        rester/rester-sql:latest   "docker-php-entrypoi…"   5 seconds ago       Up 3 seconds        443/tcp, 0.0.0.0:80->80/tcp, 9000/tcp   rester-sql
55e3f4710518        mariadb:latest             "docker-entrypoint.s…"   15 seconds ago      Up 14 seconds       3306/tcp                                db
597bc94ca1a9        redis:latest               "docker-entrypoint.s…"   About an hour ago   Up About an hour    6379/tcp                                redis
```

결과 확인:

[![Run in Postman](https://run.pstmn.io/button.svg)](https://app.getpostman.com/run-collection/b48da2f9eeab03ae91de)


## 참고 문서
자세한 예제와 설명은 설명서를 참조.

- 기본 구조
    - [기본 구조]()
- 기본 기능
    - [모듈 추가하기](https://github.com/rester-world/rester-sql/wiki/%EB%AA%A8%EB%93%88-%EC%B6%94%EA%B0%80)
    - [SQL형식의 프로시저 추가하기](https://github.com/rester-world/rester-sql/wiki/SQL-%ED%94%84%EB%A1%9C%EC%8B%9C%EC%A0%80-%EC%B6%94%EA%B0%80)
    - [PHP형식의 프로시저 추가하기](https://github.com/rester-world/rester-sql/wiki/PHP-%ED%94%84%EB%A1%9C%EC%8B%9C%EC%A0%80-%EC%B6%94%EA%B0%80)
- 설정 기능
    - [모듈 설정](https://github.com/rester-world/rester-sql/wiki/%EB%AA%A8%EB%93%88-%EC%84%A4%EC%A0%95)
    - [프로시저 설정](https://github.com/rester-world/rester-sql/wiki/%ED%94%84%EB%A1%9C%EC%8B%9C%EC%A0%80-%EC%84%A4%EC%A0%95)
- 심화 기능
    - [로그인 & 인증 기능](https://github.com/rester-world/rester-sql/wiki/%EC%9D%B8%EC%A6%9D-%EA%B8%B0%EB%8A%A5-%EC%82%AC%EC%9A%A9)
    - [내부 프로시저 호출]()
    - [접근 제한자]()
    - [동적 데이터베이스 기능]()