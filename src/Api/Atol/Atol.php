<?php

namespace Api\Atol;

use Api\Atol\Contracts\ReceiptInterface;
use Api\Atol\Contracts\ReceiptItemInterface;
use Api\Atol\Helper\ReceiptItemUnit;
use Api\Atol\Helper\VatType;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface as DecodingExceptionInterfaceAlias;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Atol
{
    private const CACHE_PREFIX = '__ATOL__';
    private TranslatorInterface $translator;
    private AdapterInterface $adapter;
    private Client $client;
    private array $credentials = [
        'login' => '',
        'password' => '',
        'group' => '',
        'company' => [
            'domain' => '',
            'email' => '',
            'sno' => '',
            'inn' => '',
        ],
    ];

    public function __construct(TranslatorInterface $translator, AdapterInterface $adapter, array $credentials = [])
    {
        $this->client = new Client(false);
        $this->credentials = \array_replace($this->credentials, $credentials);
        $this->translator = $translator;
        $this->adapter = $adapter;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws DecodingExceptionInterfaceAlias
     */
    public function auth(): Token
    {
        $response = $this->client->request('/getToken', [
            'login' => $this->credentials['login'],
            'pass' => $this->credentials['password'],
        ]);

        return new Token((string) $response['token'], new \DateTimeImmutable('+24hours'));
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws DecodingExceptionInterfaceAlias
     */
    public function sell(Token $token, ReceiptInterface $receipt): UuidInterface
    {
        $options = $this->buildSellOptions($receipt);

        $response = $this->client->request('/sell', \array_merge($options, [
            'group' => $this->credentials['group'],
            'token' => $token,
        ]));

        return Uuid::fromString($response['uuid']);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws DecodingExceptionInterfaceAlias
     */
    public function report(Token $token, UuidInterface $id): Report
    {
        $response = $this->client->request('/report/'.(string) $id, [
            'group' => $this->credentials['group'],
            'token' => $token,
        ], 'GET');

        return new Report($response);
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function loadToken(): Token
    {
        $item = $this->adapter->getItem(self::CACHE_PREFIX);

        /** @var Token $token */
        $token = null;
        if ($item->isHit()) {
            $token = $item->get();
        }

        if (null !== $token && !$token->isExpired()) {
            return $token;
        }

        $token = $this->auth();
        $item->set($token);
        $item->expiresAfter(24 * 60 * 60);
        $this->adapter->save($item);

        return $token;
    }

    public function unsetToken(): void
    {
        $this->adapter->deleteItem(self::CACHE_PREFIX);
    }

    private function buildSellOptions(ReceiptInterface $receipt): array
    {
        $translator = $this->translator;

        $total = \round(\array_sum(\array_map(function (ReceiptItemInterface $item): float {
            return $item->getPrice() * $item->getQuantity();
        }, $receipt->getReceiptItems())), 2);

        $usedVats = \array_filter(\array_unique(\array_map(function (ReceiptItemInterface $item) {
            return $item->getVatType();
        }, $receipt->getReceiptItems())), function (string $vatType): bool {
            return true;
        });

        $getVatByType = function (string $vatType) use ($receipt): float {
            $sum = 0;
            foreach ($receipt->getReceiptItems() as $item) {
                if ($item->getVatType() === $vatType) {
                    $sum += VatType::calculate($vatType, $item->getPrice() * $item->getQuantity());
                }
            }

            return \round($sum, 2);
        };

        return [
            'external_id' => (string) $receipt->getId(),
            'timestamp' => (new \DateTimeImmutable())->format('dd.mm.yyyy HH:MM:SS'),
            'receipt' => [
                'company' => [
                    'email' => $this->credentials['company']['email'],
                    'sno' => $this->credentials['company']['sno'],
                    'inn' => $this->credentials['company']['inn'],
                    'payment_address' => $this->credentials['company']['domain'],
                ],
                'client' => [
                    'email' => $receipt->getClient()->getEmail(),
                    'phone' => $receipt->getClient()->getPhone(),
                    'name' => $receipt->getClient()->getName(),
                ],
                'total' => $total,
                'payments' => [
                    [
                        'type' => 1,
                        'sum' => $receipt->getPaidAmount(),
                    ],
                ],
                //configure in outer class
                'items' => \array_map(function (ReceiptItemInterface $item) use ($translator): array {
                    $sum = \round($item->getPrice() * $item->getQuantity(), 2);

                    switch ($item->getVatType()) {
                        case VatType::VAT18:
                            $vat = [
                                'type' => $item->getVatType(),
                            ];
                            break;
                        default:
                            $vat = [
                                'sum' => VatType::calculate($item->getVatType(), $sum),
                                'type' => $item->getVatType(),
                            ];
                    }

                    return [
                        'name' => $translator->trans($item->getReceiptName()),
                        'price' => $item->getPrice(),
                        'quantity' => $item->getQuantity(),
                        'sum' => $sum,
                        'measurement_unit' => $translator->trans(ReceiptItemUnit::label($item->getUnit())),
                        'payment_method' => $item->getPaymentType(),
                        'payment_object' => $item->getType(),
                        'vat' => $vat,
                    ];
                }, $receipt->getReceiptItems()),
                'vats' => \array_map(function (string $vatType) use ($getVatByType): array {
                    return [
                        'sum' => $getVatByType($vatType),
                        'type' => $vatType,
                    ];
                }, $usedVats),
            ],
        ];
    }
}
