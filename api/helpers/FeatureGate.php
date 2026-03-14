<?php
/**
 * Feature Gate
 *
 * Central utility for subscription-plan-based feature access control.
 *
 * Usage (in any Merchant controller):
 *
 *   FeatureGate::require($user, 'coupons');
 *   // → terminates with 403 if the merchant's plan doesn't include 'coupons'
 *
 *   if (FeatureGate::has($user, 'analytics')) { ... }
 *   // → returns bool, safe for conditional rendering
 */
class FeatureGate {

    /**
     * Features available per subscription plan.
     *
     * Duplicated here as a compile-time constant so controllers don't need
     * a DB round-trip for every gate check.  Kept in sync with the
     * merchant_plan_features table via migrations.
     */
    private const PLAN_FEATURES = [
        'merchant_only' => [
            'profile',
            'stores',
        ],
        'coupon_merchant' => [
            'profile',
            'stores',
            'coupons',
            'store_coupons',
        ],
        'hybrid' => [
            'profile',
            'stores',
            'coupons',
            'store_coupons',
            'analytics',
            'reviews',
            'grievances',
            'messages',
        ],
        'advanced' => [
            'profile',
            'stores',
            'coupons',
            'store_coupons',
            'analytics',
            'reviews',
            'grievances',
            'messages',
            'loyalty_cards',
            'flash_discounts',
            'bulk_allotment',
            'customer_management',
            'sales_registry',
        ],
    ];

    /**
     * Check if the authenticated merchant has access to a feature.
     *
     * @param array  $user        The user array from AuthMiddleware::require()
     * @param string $featureKey  The feature key to check
     */
    public static function has(array $user, string $featureKey): bool {
        $plan     = $user['subscription_plan'] ?? 'merchant_only';
        $features = self::PLAN_FEATURES[$plan] ?? self::PLAN_FEATURES['merchant_only'];
        return in_array($featureKey, $features, true);
    }

    /**
     * Require a feature — terminates with 403 if the plan doesn't include it.
     *
     * @param array  $user
     * @param string $featureKey
     * @param string $humanName   Optional human-readable feature name for the error message
     */
    public static function require(array $user, string $featureKey, string $humanName = ''): void {
        if (self::has($user, $featureKey)) {
            return;
        }

        $plan    = $user['subscription_plan'] ?? 'merchant_only';
        $display = $humanName ?: $featureKey;

        Response::error(
            "Your current plan ({$plan}) does not include access to {$display}. "
            . "Please upgrade your subscription.",
            403,
            'PLAN_FEATURE_LOCKED'
        );
    }

    /**
     * Return the list of features for a given plan.
     */
    public static function featuresFor(string $plan): array {
        return self::PLAN_FEATURES[$plan] ?? self::PLAN_FEATURES['merchant_only'];
    }

    /**
     * Return all plan definitions (used by admin UI to render feature matrix).
     */
    public static function allPlans(): array {
        $out = [];
        foreach (self::PLAN_FEATURES as $plan => $features) {
            $out[] = ['plan' => $plan, 'features' => $features];
        }
        return $out;
    }
}
