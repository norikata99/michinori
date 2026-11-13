<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * 予定と実績のズレ。タスクとゴールで共通に使う。
 *
 * 実績の値（工数・開始日）は保存せず、作業ログから集計した結果を受け取る（docs/adr/0005）。
 * 日数の差は SQL ではなく PHP で計算する。SQLite（テスト）と MySQL（本番）で日付関数の書き方が違うため。
 */
final readonly class Progress
{
    public function __construct(
        // 見積もり工数（分）
        public int $estimatedMinutes,
        // 実績工数（分）。作業ログの minutes の合計
        public int $actualMinutes,
        // 予定の終了日（タスクは planned_end、ゴールは締切）
        public CarbonInterface $plannedEnd,
        // 実際に終わった日（タスクは completed_at、ゴールは achieved_at）。終わっていなければ null
        public ?CarbonInterface $finishedOn,
        // 実績開始日。作業ログの worked_on の最小値。ログがなければ null
        public ?CarbonInterface $actualStart,
    ) {}

    /**
     * 集計結果（SUM・MIN）から作る。MIN の結果は DB によって文字列で返るため、ここで日付に直す。
     */
    public static function fromAggregates(
        int $estimatedMinutes,
        int|string|null $actualMinutes,
        CarbonInterface $plannedEnd,
        ?CarbonInterface $finishedOn,
        CarbonInterface|string|null $actualStart,
    ): self {
        return new self(
            estimatedMinutes: $estimatedMinutes,
            actualMinutes: (int) $actualMinutes,
            plannedEnd: $plannedEnd,
            finishedOn: $finishedOn,
            actualStart: $actualStart === null ? null : Carbon::parse($actualStart)->startOfDay(),
        );
    }

    /**
     * 工数のズレ（分）。実績 − 見積もり。正なら見積もりより多くかかっている
     */
    public function effortGapMinutes(): int
    {
        return $this->actualMinutes - $this->estimatedMinutes;
    }

    /**
     * 日程のズレ（日）。終わった日 − 予定の終了日。正なら遅れ、負なら前倒し。終わっていなければ null
     */
    public function scheduleGapDays(): ?int
    {
        if ($this->finishedOn === null) {
            return null;
        }

        return (int) $this->plannedEnd->copy()->startOfDay()
            ->diffInDays($this->finishedOn->copy()->startOfDay(), absolute: false);
    }
}
