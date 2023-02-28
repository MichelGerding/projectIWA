# Project IWA

## start the server
to start the server youi need to have docker installed. to start the server you can run the command

```
docker compose up -d 
```

when the server has succesfully started it is accesable at `http://localhost:8000`

## stopping server 
to stop the sserver you can use the command 
```
docker compose down 
```

## executing commands
to execute commands on the server you can use the `ds.sh` file. 
this file passes the args as command to the laravel container

for example
```
ds.sh php artisan list
```

converts to 
```
docker compose exec laravel-iwa php artisan list
```


## accessing logs 
to access the logs of the docker processes you can use the command. the following command shows you the 50 most recent entrie sin the log. 
```
docker compose logs -f --tail 50
```

to access a single service you can append the service to the end