// Ratings and Reviews System
class RatingSystem {
    constructor() {
        this.stars = 5; // Maximum number of stars
        this.currentRating = 0;
    }

    init(containerId, itemId) {
        this.container = document.getElementById(containerId);
        this.itemId = itemId;
        this.render();
        this.loadExistingRating();
        this.setupEventListeners();
    }

    render() {
        // Create rating container
        const ratingContainer = document.createElement('div');
        ratingContainer.className = 'rating-container';

        // Create star rating
        const starContainer = document.createElement('div');
        starContainer.className = 'star-container';

        for (let i = 1; i <= this.stars; i++) {
            const star = document.createElement('span');
            star.className = 'star';
            star.innerHTML = '★';
            star.dataset.value = i;
            starContainer.appendChild(star);
        }

        // Create review section
        const reviewSection = document.createElement('div');
        reviewSection.className = 'review-section';
        reviewSection.innerHTML = `
            <textarea id="review-text" placeholder="Write your review here..."></textarea>
            <button id="submit-review" class="btn-primary">Submit Review</button>
        `;

        // Create reviews list
        const reviewsList = document.createElement('div');
        reviewsList.id = 'reviews-list';
        reviewsList.className = 'reviews-list';

        // Append all elements
        ratingContainer.appendChild(starContainer);
        ratingContainer.appendChild(reviewSection);
        ratingContainer.appendChild(reviewsList);
        this.container.appendChild(ratingContainer);

        // Add styles
        const styles = document.createElement('style');
        styles.textContent = `
            .rating-container {
                margin: 20px 0;
                padding: 15px;
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .star-container {
                display: flex;
                gap: 5px;
                margin-bottom: 15px;
            }
            .star {
                font-size: 24px;
                cursor: pointer;
                color: #ddd;
                transition: color 0.2s;
            }
            .star.active {
                color: #ffd700;
            }
            .star:hover {
                color: #ffd700;
            }
            .review-section {
                margin: 15px 0;
            }
            #review-text {
                width: 100%;
                min-height: 100px;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
                margin-bottom: 10px;
                resize: vertical;
            }
            .reviews-list {
                margin-top: 20px;
            }
            .review-item {
                padding: 10px;
                border-bottom: 1px solid #eee;
            }
            .review-rating {
                color: #ffd700;
                margin-bottom: 5px;
            }
            .review-text {
                color: #666;
            }
            .review-date {
                font-size: 0.8em;
                color: #999;
            }
        `;
        document.head.appendChild(styles);
    }

    setupEventListeners() {
        const starContainer = this.container.querySelector('.star-container');
        const submitButton = this.container.querySelector('#submit-review');

        // Star rating hover and click events
        starContainer.addEventListener('mouseover', (e) => {
            if (e.target.classList.contains('star')) {
                const value = parseInt(e.target.dataset.value);
                this.highlightStars(value);
            }
        });

        starContainer.addEventListener('mouseout', () => {
            this.highlightStars(this.currentRating);
        });

        starContainer.addEventListener('click', (e) => {
            if (e.target.classList.contains('star')) {
                this.currentRating = parseInt(e.target.dataset.value);
                this.highlightStars(this.currentRating);
            }
        });

        // Submit review
        submitButton.addEventListener('click', () => {
            const reviewText = this.container.querySelector('#review-text').value;
            if (this.currentRating && reviewText) {
                this.submitReview(reviewText);
            } else {
                alert('Please provide both rating and review text');
            }
        });
    }

    highlightStars(count) {
        const stars = this.container.querySelectorAll('.star');
        stars.forEach((star, index) => {
            star.classList.toggle('active', index < count);
        });
    }

    async submitReview(reviewText) {
        try {
            const response = await fetch('/api/submit_review.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    itemId: this.itemId,
                    rating: this.currentRating,
                    review: reviewText
                })
            });

            if (response.ok) {
                const result = await response.json();
                this.addReviewToList({
                    rating: this.currentRating,
                    review: reviewText,
                    date: new Date().toLocaleDateString()
                });
                this.container.querySelector('#review-text').value = '';
                this.currentRating = 0;
                this.highlightStars(0);
            } else {
                throw new Error('Failed to submit review');
            }
        } catch (error) {
            console.error('Error submitting review:', error);
            alert('Failed to submit review. Please try again.');
        }
    }

    addReviewToList(review) {
        const reviewsList = this.container.querySelector('#reviews-list');
        const reviewElement = document.createElement('div');
        reviewElement.className = 'review-item';
        reviewElement.innerHTML = `
            <div class="review-rating">${'★'.repeat(review.rating)}${'☆'.repeat(5-review.rating)}</div>
            <div class="review-text">${review.review}</div>
            <div class="review-date">${review.date}</div>
        `;
        reviewsList.insertBefore(reviewElement, reviewsList.firstChild);
    }

    async loadExistingRating() {
        try {
            const response = await fetch(`/api/get_reviews.php?itemId=${this.itemId}`);
            if (response.ok) {
                const reviews = await response.json();
                reviews.forEach(review => this.addReviewToList(review));
            }
        } catch (error) {
            console.error('Error loading reviews:', error);
        }
    }
}

// Export the RatingSystem class
window.RatingSystem = RatingSystem;