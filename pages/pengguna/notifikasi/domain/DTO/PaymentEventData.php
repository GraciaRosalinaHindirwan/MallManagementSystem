<?php

class PaymentEventData
{
    public function __construct(
        public readonly string $type,
        public readonly int $user_id,
        public readonly string $email,
        public readonly string $name,
        public readonly ?string $invoice = null,
        public readonly ?int $amount = null,
        public readonly ?string $due_date = null
    ) {}

    public static function fromArray(array $data): self
    {
        self::validate($data);

        return new self(
            type: $data['type'],
            user_id: $data['user_id'],
            email: $data['email'],
            name: $data['name'],
            invoice: $data['invoice'] ?? $data['invoice_id'] ?? null,
            amount: $data['amount'] ?? null,
            due_date: $data['due_date'] ?? null,
        );
    }

    private static function validate(array $data): void
    {
        $required = ['type', 'user_id', 'email', 'name'];

        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new InvalidArgumentException("$field wajib diisi");
            }
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("email tidak valid");
        }
    }
}