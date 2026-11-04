<?php
// TEMPORARY: fake data so the pages work before the database exists.
// Every array has the same fields as a future DB row, so the templates
// will not change when this class is replaced by real classes (Movie, Showing, ...).

class SampleData
{
    private const CAPACITY = 100;

    // Ticket prices (kr). Later these live in the database.
    public const PRICE_STANDARD = 150;
    public const PRICE_LUXURY   = 185;
    public const BOOKING_FEE    = 5;

    // Fallback backdrop for the booking page hero (featured movie has no poster).
    private const HERO_BACKDROP = 'https://images.alphacoders.com/141/thumb-1920-1412625.jpg';

    // The big movie in the hero section
    public static function featuredMovie(): array
    {
        return [
            'title'       => 'DoomsDay',
            'duration'    => 105,
            'description' => 'A catastrophic multiversal crisis known as "incursions"—where distinct universes collide, threatening to destroy one or both realities entirely',
        ];
    }

    // Quick showtime chips under the hero (today's showings of the featured movie)
    public static function todayShowings(): array
    {
        return [
            self::slot(1, 1, '13:30', '2D, Eng subs', 60),
            self::slot(2, 1, '16:00', '2D, Eng subs', 60),
            self::slot(3, 4, '18:15', '2D, Eng subs', 60),
            self::slot(4, 5, '20:45', '2D, Eng subs', 12),
        ];
    }

    // Poster row under the hero. The first movie is shown as "active".
    public static function nowShowing(): array
    {
        return [
            ['id' => 1, 'title' => 'Dobbeltfejl', 'genre' => 'Drama', 'duration' => 105,
             'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBzFjqO164yaDgedltR6NjV4x89cmLDzmqShLAaW2tffPIcEvxP82N4WW8hLybTn67KTqxHj1_OwAN14wlw6nwd3MpxSGIuQf2HcAQC58o4TeaJU-p1Wqi4BP5uQfq_SSOAy8YOXb3iaBYqCGYoHhfgJrO0XApCawv4K6Ze_8M0I2mf--g_X6OAesE0MsCyh9BR_sqklj46980zaQfgZ0Q7wAmB5GlVqpxHzgP4OyjBH7mLnNZUFPQP9w'],
            ['id' => 2, 'title' => 'The Bastard', 'genre' => 'Historical Drama', 'duration' => 127,
             'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCHvSzMhDs0buYPRpzBEJmblP6e7pymg9tHT_pCou1D6ibUkhejm2pGJcpFLNl8sr3FiowAw_z7r3iMQHaEYX6wvp8BqydGMLZiyL096Pid6_wNWwNX30KTT-pxiIKPG611XQTGju2AZ9MQlhlN39LHZsDFWYA9wcM3EkNLLJNvw671Q67De5k7yAXl4XTNI8uVJepSaAVo9ZMNEhT8zS1mXvRUjulfDpw2KhPsSjTfyeIr-6EupWppMQ'],
            ['id' => 3, 'title' => 'Dune: Part Two', 'genre' => 'Sci-Fi IMAX', 'duration' => 166,
             'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCEZIi3f1FEbcN6pj-r9aH1DPcJ0uGVQtgXhIBpxY3FEGods6wArwbmcm97iewF-wPIZ6-qjTf-c7SFRJ1jwFWXIw7bZgBo1QAO8PH0W5JSS8Cgrs7OCImm3D82lMb0iDv1aQVog1VnjEQcKjW-AzQbFRSwcVN2ezp-1SVuO57pjt9Dmbl0B0fU6aFpneRQFIwJMTRRdAfHPhpDy5jV3azvTCRW-ojH2R1kR59mZCX4Adbhx4U4zjrI_A'],
            ['id' => 4, 'title' => 'Anatomy of a Fall', 'genre' => 'Mystery', 'duration' => 151,
             'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAl2UZUngb6cFCH3HqOhiYNW2-jWg-oY5lvBEos0vOENGoBhRh245985O9p4Wq5fAY43kSUnrrmOoWjxL_pb9PTseV-bwpk9PhUgwyQZM9HQzEBFGoPJKupZOhYOunYH-3cqFfqzZldiP-VDSd9OW_m6QC-ZEVkH0lX9WMwhQQV5_zoqR_dJ3KqNEbkC0GfX3bD9fJ77kwu4ZCnN2as4Yyu6yNhJ9yzPq4yyMPcoZEzUlnJqJD5YnjCaQ'],
            ['id' => 5, 'title' => 'The Hypnosis', 'genre' => 'Satire', 'duration' => 98,
             'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBBQCVYp_n2tieurQPnCOiut1b_bdOLv4J_4ib-DIK1KDBQxS8G5KZbrqwUuBG4QAICan_zLKtbAJAHQcDOCP-SRzCywP5_sgfHVnmfXADJuNVb4hdzWNbklnv-bPfeWSoqfh69BRLf9PTLQ-1kG9rx2_qtbbLrtwajXFQqJwJ4qgujrU3rVCol8P7NN7YKOtSyO0Iu_b_fX7MlnO-YDX3HFNeSOhg5joJ6TuudWRgYrDye_hHcJwxuhg'],
            ['id' => 6, 'title' => 'The Zone of Interest', 'genre' => 'Drama', 'duration' => 105,
             'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBx_IufrvhNUJyo1LqQjO8-ZYH12omSt_rHWCJCPsE4YJQXtP0l8Y-2tDpjlu7Sz8VOXa9_TMsFD1rManqpAFItswhQP1MOE3nogI627SV2CRETasD22oY5MxoeG0sf6syR_4eq3HpPCPP-oEfLfIyDfAPhsv6eQx0rjKWWz5Gsji0ODzjSenP6UrHhZ1jzdzSCfUIGNCDoxPm-rDe5_tnUl9jyaH2GJ9pTR6JhlXPcOCog0v7EbE1zUw'],
        ];
    }

    // The 7 days starting today. Used for the date pills and the matrix columns.
    public static function weekDays(): array
    {
        $days = [];
        $today = new DateTimeImmutable('today');
        for ($i = 0; $i < 7; $i++) {
            $d = $today->modify("+$i day");
            $days[] = [
                'date'  => $d->format('Y-m-d'),
                'label' => $d->format('D, j M'),
                'pill'  => ($i === 0 ? 'Today • ' : '') . $d->format('D j'),
            ];
        }
        return $days;
    }

    // Showtime matrix: each movie has a list of slots for each of the 7 days (index 0 = today).
    public static function weekSchedule(): array
    {
        $id = 100;
        $fmt = '2D, Eng subs';
        $atmos = 'Atmos Sound';

        return [
            [
                'title'    => 'Dobbeltfejl (Double Fault)',
                'duration' => 105,
                'poster'   => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAlKB1xIruPlbmMMhsV38rxfc-vWteuCYpYIm-8Y1GyRlv6YOUYOIbRPZMFgBeiCpCWRu9_tyh0SKBCiawCJwI3qq-UlZWDX_uRtd5t5f6l3c-HZ1plfxPbH4Zl7msL_YAeNmgYqSZH6Djw2lYUJoIcz1YbSrbYvFOnGUHVooDwZUMbvCeN6Y7WKhmuSmVFlu3uM_j79GJC0YHe3bfyrdSDj-hzY1sS1r6zsffI1PI_64fgx6Yjrg-Vtg',
                'days'     => [
                    [self::slot($id++, 1, '13:30', $fmt, 60), self::slot($id++, 1, '16:00', $fmt, 60), self::slot($id++, 4, '18:15', $fmt, 60), self::slot($id++, 5, '20:45', $fmt, 60)],
                    [self::slot($id++, 1, '13:45', $fmt, 60), self::slot($id++, 1, '16:15', $fmt, 60), self::slot($id++, 1, '18:45', $fmt, 60), self::slot($id++, 4, '20:45', $fmt, 60)],
                    [self::slot($id++, 1, '13:30', $fmt, 60), self::slot($id++, 1, '16:00', $fmt, 60), self::slot($id++, 2, '18:15', $fmt, 60), self::slot($id++, 6, '20:50', $fmt, 60)],
                    [self::slot($id++, 1, '16:00', $fmt, 12), self::slot($id++, 2, '19:00', $fmt, 60)],
                    [self::slot($id++, 1, '17:30', $fmt, 4), self::slot($id++, 1, '20:30', $fmt, 0)],
                    [self::slot($id++, 1, '14:00', $fmt, 60), self::slot($id++, 2, '17:00', $fmt, 15), self::slot($id++, 1, '20:00', $fmt, 0)],
                    [self::slot($id++, 1, '15:30', $fmt, 60), self::slot($id++, 4, '18:30', $fmt, 60)],
                ],
            ],
            [
                'title'    => 'The Bastard (The Promised Land)',
                'duration' => 127,
                'poster'   => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBUxMikBx5QV-2HAodGNiB_6PmyyTbtA3VAtjNTqC3HsrdgNqfh4WsyyCltmFbT5QUKXRYjWk0FB-qooby4UPUifh2Cad1_Kxg7A4t6HkQLvFGwkHXPHCuiPvIpyejnN0kjLdr4oVpiuQjidsisJkFlXmHg2jIaKy8Ecp_RVvC1IddgBZjGsYw8YSeIEESYMgaSAxYcc8vREI54CMi43VJxsDAB3hQQQVOnRm0ek1KNYOgFWArd78llog',
                'days'     => [
                    [self::slot($id++, 2, '14:15', $atmos, 60), self::slot($id++, 2, '19:30', $atmos, 60)],
                    [self::slot($id++, 2, '14:30', $atmos, 60), self::slot($id++, 2, '20:00', $atmos, 15)],
                    [self::slot($id++, 2, '15:00', $atmos, 60), self::slot($id++, 2, '19:15', $atmos, 60)],
                    [self::slot($id++, 2, '17:00', $atmos, 60)],
                    [self::slot($id++, 2, '18:00', $atmos, 15), self::slot($id++, 2, '21:15', $atmos, 0)],
                    [self::slot($id++, 2, '16:30', $atmos, 60)],
                    [self::slot($id++, 2, '17:15', $atmos, 60)],
                ],
            ],
        ];
    }

    // News / events cards
    public static function events(): array
    {
        return [
            [
                'id' => 1, 'highlight' => true, 'date_label' => '24 October',
                'category' => 'Q&A • Exclusive preview',
                'title' => 'Director Visit: Meet the team behind Double Fault',
                'excerpt' => "Experience an in-depth conversation with the film's director and lead cast in Screen 1, followed by a glass of natural wine in the foyer.",
                'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCQLCJI6A-6erOmj0bVtbSaQqL8xGXSOhKdppbG_aSQ8TT7U0U66xF1scl6DdRqp5O4NqB9xRgFnx8k3KhsW30kw9iQscxUFgQFMmkRFsRSzo7V2_Y7E5avCvWM-QyRfaLtlV2ZIIrm6e71yqlZVNJVYY8Hnts9um9QbACZBdtRewlo5GQWYdi8RgQ9dKLACWqwsR_V_1chPRjLp_jfQ5EMRkRW500e_DSjIeyEVATs7LQ8GFz4xFwMJg',
                'link_text' => 'Read more & book tickets',
            ],
            [
                'id' => 2, 'highlight' => false, 'date_label' => '12 – 18 November',
                'category' => 'Analog film • Festival',
                'title' => 'Lumen 70mm Film Festival returns',
                'excerpt' => 'Experience historic masterworks and modern epics in legendary analog 70mm widescreen format in our recalibrated Screen 4.',
                'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBuzVz_ifz1KUpUbcDYWL71lG2CgiJRw6tIFK0swHWwaDe2nAUXMx5MVsTN8zfes3JECgffAmTElaQKXjo736IF_5vysyfCbPWjmpcJMviwwOzLAp60tNfWf1PlelerRrWQ5_z7VJdhvA1wAqoJcWUOrS0jPCTf3ukW-beMQrozRE0uewMkLRWfYSVsLfh1ehH6IZNuMgLeI3cnk_Wpw2wu6dDLXpuqmat0SG2yUHzuT465MnNCSTyAGA',
                'link_text' => 'View programme',
            ],
            [
                'id' => 3, 'highlight' => false, 'date_label' => 'New opening',
                'category' => 'Gastronomy • Bar',
                'title' => 'Gourmet & Cinema: Wine bar and artisanal snacks',
                'excerpt' => 'Our new foyer bar serves hand-picked natural wines, organic draft beer from local microbreweries, and warm popcorn with Vesterhavsost aged cheese.',
                'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAHB5xHfW-j9VjVV_mQ6vJc6abxKrNpMgF_w9sT6v_bm94O6Rd9E9UtkCxaf6yvxNlwQS3344WYErCa_bilUYhrlrOKn1-55NN4JPtMyR_Vc7aa6WogJeH2c1VWnLYGkP3louhslhnN5wrguL3FhOWj0CCz0MoI3GPPq7agJ7071nMs1T-gZ9f97VsFy290V0UAOxiMgrg7pdXRxrpm3kqMhKb1fasvJkuexZ3KGb_E2kTZyhwSWo3Nqg',
                'link_text' => 'Learn more',
            ],
        ];
    }

    // Find one showing by its id. The booking page uses this.
    // It reuses the same arrays the front page already shows, so the seating
    // plan always matches the showtime the user clicked. Returns null if unknown.
    // Later this becomes: SELECT ... FROM showings WHERE id = ? (one Showing object).
    public static function showingById(int $id): ?array
    {
        // 1) The "today" chips under the hero belong to the featured movie.
        $featured = self::featuredMovie();
        foreach (self::todayShowings() as $slot) {
            if ((int) $slot['id'] === $id) {
                return self::showing($slot, $featured['title'], $featured['duration'], self::HERO_BACKDROP, 'Today');
            }
        }

        // 2) The weekly programme matrix (ids from 100 up).
        $days = self::weekDays();
        foreach (self::weekSchedule() as $movie) {
            foreach ($movie['days'] as $dayIndex => $slots) {
                foreach ($slots as $slot) {
                    if ((int) $slot['id'] === $id) {
                        $label = $days[$dayIndex]['label'] . ($dayIndex === 0 ? ' (Today)' : '');
                        return self::showing($slot, $movie['title'], $movie['duration'], $movie['poster'], $label);
                    }
                }
            }
        }

        return null;
    }

    // The seats for one auditorium, as 8 rows.
    // Which seats are sold is derived from the showing id, so the same showing
    // always shows the same taken seats (stable, like real data would be).
    // Later this becomes the "seat availability" query / view for that showing.
    public static function seatMap(int $showingId): array
    {
        $seatsPerRow = 18;
        $rows = [];

        for ($r = 1; $r <= 8; $r++) {
            $seats = [];
            for ($n = 1; $n <= $seatsPerRow; $n++) {
                // Seat category.
                $type = 'standard';
                if ($r >= 7 && $n >= 6 && $n <= 13) {
                    $type = 'luxury';                    // comfy middle seats, back two rows
                }
                if ($r === 7 && ($n === 1 || $n === 2)) {
                    $type = 'accessible';                // two wheelchair spaces, aisle side
                }

                // Stable "sold" pattern: a simple hash of the showing + seat.
                $hash   = ($showingId * 73 + $r * 17 + $n * 29) % 100;
                $state  = $hash < 20 ? 'sold' : 'available';   // ~20% already sold

                $seats[] = [
                    'id'    => 'R' . $r . 'S' . $n,
                    'row'   => $r,
                    'num'   => $n,
                    'type'  => $type,
                    'state' => $state,
                    'price' => $type === 'luxury' ? self::PRICE_LUXURY : self::PRICE_STANDARD,
                ];
            }
            $rows[] = ['row' => $r, 'seats' => $seats];
        }

        return $rows;
    }

    // Merge a showtime slot with its movie into one flat "showing" row.
    private static function showing(array $slot, string $title, int $duration, string $backdrop, string $dateLabel): array
    {
        return [
            'id'         => (int) $slot['id'],
            'title'      => $title,
            'duration'   => $duration,
            'screen'     => (int) $slot['screen'],
            'time'       => $slot['time'],
            'format'     => $slot['format'],
            'date_label' => $dateLabel,
            'backdrop'   => $backdrop,
            'price'      => self::PRICE_STANDARD,
        ];
    }

    // Build one showing (same fields as a row from the future showings view)
    private static function slot(int $id, int $screen, string $time, string $format, int $seatsLeft): array
    {
        return [
            'id'         => $id,
            'screen'     => $screen,
            'time'       => $time,
            'format'     => $format,
            'seats_left' => $seatsLeft,
            'capacity'   => self::CAPACITY,
        ];
    }
}
