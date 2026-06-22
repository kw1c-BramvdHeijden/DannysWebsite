const revealItems = document.querySelectorAll(".rule-card, .highlight-card, .illustration-card");

if ("IntersectionObserver" in window) {
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("visible");
                    observer.unobserve(entry.target);
                }
            });
        },
        {
            threshold: 0.18,
        }
    );

    revealItems.forEach((item) => {
        item.classList.add("reveal");
        observer.observe(item);
    });
} else {
    revealItems.forEach((item) => {
        item.classList.add("visible");
    });
}

const scoreBadge = document.querySelector("[data-target]");

if (scoreBadge) {
    const numberNode = scoreBadge.querySelector(".score-number");
    const target = Number(scoreBadge.dataset.target);

    if (numberNode && Number.isFinite(target) && target > 0) {
        let current = 0;

        const tick = () => {
            current += 1;
            numberNode.textContent = String(current);

            if (current < target) {
                window.setTimeout(tick, 80);
            }
        };

        if ("IntersectionObserver" in window) {
            const scoreObserver = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            tick();
                            scoreObserver.disconnect();
                        }
                    });
                },
                {
                    threshold: 0.5,
                }
            );

            scoreObserver.observe(scoreBadge);
        } else {
            tick();
        }
    }
}
