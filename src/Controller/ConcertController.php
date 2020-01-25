<?php
namespace App\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use App\Service\DistanceService;
use App\Entity\Bands;
use App\Entity\Concerts;
use App\Entity\Venues;

/**
 * Band controller.
 * @Route("/", name="api_")
 */
class ConcertController extends FOSRestController
{
    /**
     * @Rest\Get("/concerts")
     * @Rest\QueryParam(
     *     name="bandIds",
     *     requirements="([0-9]+,?)+",
     *     nullable=true,
     * ),
     * @Rest\QueryParam(
     *     name="latitude",
     *     requirements="\-?[0-9]*\.?[0-9]+",
     *     nullable=true,
     * ),
     * @Rest\QueryParam(
     *     name="longitude",
     *     requirements="\-?[0-9]*\.?[0-9]+",
     *     nullable=true,
     * ),
     * @Rest\QueryParam(
     *     name="radius",
     *     requirements="\d+",
     *     nullable=true,
     * ),
     * @return Response
     */
    public function getConcertsAction(ParamFetcherInterface $paramFetcher, DistanceService $dService)
    {
        $bandsId   = $paramFetcher->get('bandIds');
        $latitude  = $paramFetcher->get('latitude');
        $longitude = $paramFetcher->get('longitude');
        $radius    = $paramFetcher->get('radius');

        if (empty($bandsId) && empty($latitude) && empty($longitude) && empty($radius)) {
            $response = new Response('Bad request', Response::HTTP_BAD_REQUEST);
            $response->send();
        }

        $queryParam = [];
        if (!empty($bandsId)) {
            $queryParam['bandid'] = explode(',',$bandsId);
        }
        if (!empty($latitude) && !empty($longitude) && !empty($radius)) {
            $venuesList = $dService->getVenuesByDistance($latitude, $longitude, $radius);
            if (!empty($venuesList))
            $queryParam['venueid'] = $venuesList;
        }


        $return = [];
        // QueryParam can be empty if no bandId has been specified and we can't find Venues with the specified radius
        if(!empty($queryParam)) {
            $concerts = $this->getDoctrine()->getRepository(Concerts::class);

            $concerts = $concerts->findBy(
                $queryParam,
                ['date'=> 'DESC']
            );

            foreach($concerts as $concert) {
            $concat = [];

            $concat['band'] = $concert->getBandId()->getName();
            $concat['location'] = $concert->getVenueId()->getName();
            $concat['date'] = $concert->getDate();
            $concat['latitude'] = $concert->getVenueId()->getLatitude();
            $concat['longitude'] = $concert->getVenueId()->getLongitude();

            $return[] = $concat;
            }
        }

        $response = new Response();
        $response->setContent(json_encode([
            $return
        ]));

        $response->send();
    }
}