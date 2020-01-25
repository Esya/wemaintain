## Part 1: Development

### How to:
*Tools needed:*
- docker (https://docs.docker.com/install/)
- docker-compose (https://docs.docker.com/compose/install/)

*Start the project*:
Simply launch the script
```
./install.sh
```
depending of your OS you may need to sudo it.

*Where to find the code*: 
- src/Entity/*
- src/Controller/ConcertController.php
- src/Service/DistanceService.php


Application should be ready to receive GET queries to:
http://localhost:8000/concerts

*Examples*:

Returns all the concerts in the center of Paris within a radius of 20 km:
http://localhost:8000/concerts?longitude=2.350144&latitude=48.857931&radius=20

Returns all the concerts for bands 1 2 & 42:
http://localhost:8000/concerts?bandIds=1,2,42

Returns all the concerts for bands 1 2 & 42 in the center of Paris within a radius of 20km:
http://localhost:8000/concerts?bandIds=1,2,42&longitude=2.350144&latitude=48.857931&radius=20

Empty array:
http://localhost:8000/concerts?bandIds=3784


*Errors*:
http://localhost:8000/concerts
https://localhost:8000/concerts?band=32
400 Bad Request

http://localhost:8000/
http://localhost:8000/concert
404 Not found


----------------------------

## Part 2: Architecture
For the previous step, we had 1400 bands across 376 venues, and around 20,000 events. For this step, we ask that you document in your README.md how you would architecture your solution if you now had 2 million bands, 10,000 venues, and 200 million events.
Describe in detail how you would store and query the data, and what kind of mechanism you would leverage to consistently deliver short response times and guarantee the highest uptime possible.


I would use Apache Cassandra:

The table satisfy the query we want to make.

```
CREATE KEYSPACE events_keyspace WITH replication={'class': 'SimpleStrategy', 'replication_factor': n-1}
```
Replication factor is the value of in which nodes we want the data to be replicated. The maximum value for replication_factor is n-1 where n is the total number of nodes minus 1.

The events_keyspace would have one table per query we want to make in the futur.
We want to get all concerts by the name of band and filter with location, ordered by Data.

Table for getting all concerts for a band.
```
CREATE TABLE concert_by_band_sorted (bandName text, date INT, venueName text, longitude double, latitude double, PRIMARY KEY(bandName, date)) WITH CLUSTERING ORDER BY (date DESC);
```
The partition key is bandName, it will use to shard data to specific nodes.
Primary key date will be use to know how the data will be sorted in nodes.


```
SELECT * FROM concert_by_band_sorted WHERE bandName="Toto";
```
Will return all concerts for the band "Toto" sorted by Date.


```
SELECT * FROM concert_by_bands
    WHERE  latitude > 3.3454563 
    AND latitude < 4.43532 
    AND longitude > 2.3545
    AND longitude < 3.45463
    AND bandName="Toto";
```
Will return all concerts for the band "Toto" within a range sorted by date.


**Please then answer the two following questions :**

*What do you identify as possible risks in the architecture that you described in the long run, and how would you mitigate those?*

The calculation of the maximum longitude/latitude is always carried out by the application. It would be more efficient to use Geohash instead of latitude/longitude.
Protect us from Murphy's laws with Multi zones architecture (e.g for AWS : eu-west-1a eu-west-1b eu-west-1c)

*What are the key elements of your architecture that would need to be monitored, and what would you use to monitor those?*


For the data part:

The key elements are cluters/nodes health that can be check by nodetool.
We can also do that with Datadog directly, who accepts integration of Cassandra cluster.

What we need to be monitored :

- If the load are the same between nodes.
- Latency of reads/writes
- Disk Usage (Read/write, spaces, load)
- Exceptions count from Cassandra

For the API:
- Containers Memory/CPU load (Prometheus+Grafana or Datadog)
- Transactions time (Datadog APM)
- Errors logs (ElasticSearch/Kibana/Logstash Stack or Datadog Logs)


------------------------

## Part 3: Infrastructure

**A. How would you proceed to forbid/restrict access to the cloud production database (eg: RDS), while allowing access from team members ?**

The RDS will be on a specific VPC, external access are disabled. The Security group of RDS instance will only authorize incomming connection from other Security Group on MySQL Port (3306).

I will mount a VPN (With EC2 instance (e.G: OpenVPN) or with Client VPN Endpoints from AWS) that can access the VPC of the RDS instance with peering connections.
Only the VPN VPC will have an internet gateway.

Each user has a personnal VPN login and each user has a personnal login to connect to MySQL with correct permission.

**B. What kind of infrastructure would you deloy to run the BandService alongside other similar services ? What would you implement in order to guarantee the best uptime ?**

A Kubernetes cluster with:

3 masters and at least 3 nodes on multi zones with a NetworkLoadBalancer or ElasticLoadBalancer in front with listeners to our ingress service.
An autoscalling group that can deploy new nodes/masters if needed.

In front of our services, a Nginx LoadBalancer (Ingress) that will route queries to the right service.
 e.g: For our BandService, the host domain.com with path '/concerts' will redirect to our containers BandService on the right port.
        OtherService, the host domain.com with path '/others' will redirect to our containers OtherService.

For our services:

Health check and Readiness prob for all deployments so we can deploy new containers without downtime. By using Blue/Green deployment method, we can create all news pods with new Docker image, then after stop the older pods. With this method we don't have on live 2 pods with 2 different API version and 0 downtime.

**C. How would you achieve a continuous deployment workflow, that would allow, upon a commit in the develop branch, to deploy new versions in staging and in production without any action from the engineering team. While guaranteeing the reliability of the production environment ?**

Using Github/Gitlab webhooks and Jenkins pipeline or Gitlab CI/CD

*For staging deployment:*
When a new commit is merged on staging branch, a jenkins|gitlab pipeline is launched.

We lauch applications tests (UnitTest/Functionnal test etc...) on  specifics docker image that contains dev dependencies.
If all tests are validated, Jenkins create a new Docker image with the staging branch code, the docker image is tagged with :staging then pushed to a docker repository
Check the healthcheck of the Docker image created
Restart containers concerned in the Staging namespace of the kubernetes Cluster.

deployment.yml:
image: *.dkr.ecr.eu-west-3.amazonaws.com/clients/bandService:staging
imagePullPolicy: Always

With this policy, kubernetes will always get the lastest docker image tagged :staging

*For production deployment:*
When a new TAG is created on Master branch, a jenkins|gitlab pipeline is launched
Launch all applications tests
We build the production docker Image, this image will be tagged with :$github_tag and pushed to a docker repository.
Check the healthcheck of the docker image created.
Update deployment in Kubernetes with the new image 
e.g: kubectl patch --namespace=production  BandService -p'{"spec":{"template":{"spec":{"containers":[{"name":"'$client'","image":"*.dkr.ecr.eu-west-3.amazonaws.com/clients/bandService:'$github_tag'"}]}}}}'