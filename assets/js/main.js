/**
 * Falhen Media — Main JavaScript Logic (Font Awesome Icons)
 */

document.addEventListener('DOMContentLoaded', () => {
  // 1. Sticky Navigation Bar
  const header = document.querySelector('.header');
  window.addEventListener('scroll', () => {
    if (window.scrollY > 50) {
      header?.classList.add('scrolled');
    } else {
      header?.classList.remove('scrolled');
    }
  });

  // 2. Mobile Nav Toggle
  const navToggle = document.querySelector('.nav-toggle');
  const navCenter = document.querySelector('.nav-center');
  const navMenu = document.querySelector('.nav-menu');

  if (navToggle) {
    navToggle.addEventListener('click', () => {
      const isActive = navCenter ? navCenter.classList.toggle('active') : navMenu?.classList.toggle('active');
      if (navMenu) navMenu.classList.toggle('active', isActive);
      document.body.classList.toggle('mobile-menu-open', isActive);
      const icon = navToggle.querySelector('i');
      if (icon) {
        icon.className = isActive ? 'fa-solid fa-xmark' : 'fa-solid fa-bars';
      }
    });
  }

  // 2b. Mobile Services Dropdown Accordion Toggle
  const dropdownNavItems = document.querySelectorAll('.nav-item.dropdown');
  dropdownNavItems.forEach(item => {
    const link = item.querySelector('.nav-link');
    if (link) {
      const handleMobileDropdownToggle = (e) => {
        if (window.innerWidth <= 768) {
          e.preventDefault();
          e.stopPropagation();
          item.classList.toggle('open');
        }
      };

      link.addEventListener('click', handleMobileDropdownToggle);
      
      // Also close mobile drawer menu when any sub-link inside mobile-sub-menu is clicked
      const subLinks = item.querySelectorAll('.mobile-sub-link');
      subLinks.forEach(subLink => {
        subLink.addEventListener('click', () => {
          if (window.innerWidth <= 768) {
            navCenter?.classList.remove('active');
            navMenu?.classList.remove('active');
            document.body.classList.remove('mobile-menu-open');
            const toggleIcon = navToggle?.querySelector('i');
            if (toggleIcon) toggleIcon.className = 'fa-solid fa-bars';
          }
        });
      });
    }
  });

  // 3. Video Modal Lightbox
  const modal = document.getElementById('videoModal');
  const modalIframe = document.getElementById('modalIframe');
  const modalClose = document.getElementById('modalClose');
  const playButtons = document.querySelectorAll('.play-btn, .trigger-video');

  playButtons.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const videoUrl = btn.getAttribute('data-video') || 'https://www.youtube.com/embed/ySus5ZS0b94?autoplay=1';
      if (modalIframe && modal) {
        modalIframe.src = videoUrl.includes('?') ? `${videoUrl}&autoplay=1` : `${videoUrl}?autoplay=1`;
        modal.classList.add('active');
      }
    });
  });

  if (modalClose && modal) {
    modalClose.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => {
      if (e.target === modal) closeModal();
    });
  }

  function closeModal() {
    if (modal && modalIframe) {
      modal.classList.remove('active');
      modalIframe.src = '';
    }
  }

  // 4. Portfolio Filter Tabs
  const filterBtns = document.querySelectorAll('.filter-btn');
  const portfolioItems = document.querySelectorAll('.portfolio-card');

  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const filter = btn.getAttribute('data-filter');

      portfolioItems.forEach(item => {
        const cat = item.getAttribute('data-category');
        if (filter === 'all' || cat === filter) {
          item.style.display = 'block';
        } else {
          item.style.display = 'none';
        }
      });
    });
  });

  // 5. Contact & Quote AJAX Form Handler
  const inquiryForm = document.getElementById('inquiryForm');
  const formFeedback = document.getElementById('formFeedback');

  if (inquiryForm) {
    inquiryForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const submitBtn = inquiryForm.querySelector('button[type="submit"]');
      const originalText = submitBtn ? submitBtn.innerHTML : 'Submit';

      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting...';
      }

      try {
        const formData = new FormData(inquiryForm);
        const response = await fetch('/api/submit_inquiry.php', {
          method: 'POST',
          body: formData
        });
        const result = await response.json();

        if (formFeedback) {
          formFeedback.style.display = 'block';
          if (result.success) {
            formFeedback.className = 'badge';
            formFeedback.style.background = 'rgba(34, 197, 94, 0.15)';
            formFeedback.style.borderColor = 'rgba(34, 197, 94, 0.4)';
            formFeedback.style.color = '#4ade80';
            formFeedback.innerHTML = `<i class="fa-solid fa-circle-check"></i> ${result.message}`;
            inquiryForm.reset();
          } else {
            formFeedback.className = 'badge';
            formFeedback.style.background = 'rgba(239, 68, 68, 0.15)';
            formFeedback.style.borderColor = 'rgba(239, 68, 68, 0.4)';
            formFeedback.style.color = '#f87171';
            formFeedback.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i> ${result.message}`;
          }
        }
      } catch (err) {
        if (formFeedback) {
          formFeedback.style.display = 'block';
          formFeedback.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> An unexpected error occurred. Please try again.';
        }
      } finally {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalText;
        }
      }
    });
  }

  // 6. Impact Counter Scroll Animation
  const counterElements = document.querySelectorAll('.impact-number[data-count]');
  if (counterElements.length > 0) {
    let animated = false;

    const animateCounters = () => {
      counterElements.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-count'), 10);
        const duration = 1800; // 1.8s
        const stepTime = 20;
        const steps = duration / stepTime;
        const increment = target / steps;
        let current = 0;

        const timer = setInterval(() => {
          current += increment;
          if (current >= target) {
            current = target;
            clearInterval(timer);
          }
          counter.innerHTML = Math.floor(current) + '<span class="impact-plus">+</span>';
        }, stepTime);
      });
    };

    const impactSection = document.querySelector('#impact');
    if (impactSection && 'IntersectionObserver' in window) {
      const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
          if (entry.isIntersecting && !animated) {
            animated = true;
            animateCounters();
            obs.disconnect();
          }
        });
      }, { threshold: 0.25 });
      observer.observe(impactSection);
    } else {
      animateCounters();
    }
  }

  // 7. Featured Work Showcase Player & Category Filter
  const showcaseBg = document.getElementById('showcase-player-bg');
  const showcaseCategory = document.getElementById('showcase-category');
  const showcaseClient = document.getElementById('showcase-client');
  const showcaseTime = document.getElementById('showcase-time');
  const showcaseTitle = document.getElementById('showcase-title');
  const showcasePlayBtn = document.getElementById('showcase-play-btn');
  const showcaseFullBtn = document.getElementById('showcase-full-btn');
  const showcaseCurrent = document.getElementById('showcase-current');
  const showcaseTotal = document.getElementById('showcase-total');
  const thumbCards = Array.from(document.querySelectorAll('.thumb-card'));
  const filterPillBtns = document.querySelectorAll('.filter-pill-btn');
  const prevBtn = document.getElementById('showcase-prev-btn');
  const nextBtn = document.getElementById('showcase-next-btn');

  if (thumbCards.length > 0) {
    let currentIndex = 0;
    let autoTimer = null;

    if (showcaseTotal) {
      showcaseTotal.textContent = thumbCards.length;
    }

    const activateShowcaseItem = (index) => {
      if (index < 0) index = thumbCards.length - 1;
      if (index >= thumbCards.length) index = 0;
      currentIndex = index;

      thumbCards.forEach((card, idx) => {
        if (idx === currentIndex) {
          card.classList.add('active');
          if (!card.querySelector('.thumb-badge-now')) {
            const badge = document.createElement('div');
            badge.className = 'thumb-badge-now';
            badge.textContent = 'NOW';
            card.appendChild(badge);
          }
        } else {
          card.classList.remove('active');
          const badge = card.querySelector('.thumb-badge-now');
          if (badge) badge.remove();
        }
      });

      const activeCard = thumbCards[currentIndex];
      if (activeCard) {
        const cat = activeCard.dataset.category || 'SOCIAL EVENT';
        const client = activeCard.dataset.client || 'Halima';
        const time = activeCard.dataset.time || '1:23';
        const title = activeCard.dataset.title || '40th Birthday Celebration';
        const img = activeCard.dataset.img || '/assets/img/portfolio/portfolio_halima.png';
        const video = activeCard.dataset.video || 'https://www.youtube.com/embed/ySus5ZS0b94';

        if (showcaseBg) showcaseBg.style.backgroundImage = `url('${img}')`;
        if (showcaseCategory) showcaseCategory.textContent = cat;
        if (showcaseClient) showcaseClient.textContent = client;
        if (showcaseTime) showcaseTime.textContent = time;
        if (showcaseTitle) showcaseTitle.textContent = title;
        if (showcasePlayBtn) showcasePlayBtn.setAttribute('data-video', video);
        if (showcaseFullBtn) showcaseFullBtn.setAttribute('data-video', video);
        if (showcaseCurrent) showcaseCurrent.textContent = currentIndex + 1;
        if (showcaseTotal) showcaseTotal.textContent = thumbCards.length;

        // Smooth scroll active thumbnail card into view within the carousel wrapper
        const wrapper = document.querySelector('.showcase-thumbs-wrapper');
        if (wrapper && activeCard) {
          const cardLeft = activeCard.offsetLeft;
          const cardWidth = activeCard.offsetWidth;
          const wrapperWidth = wrapper.offsetWidth;
          wrapper.scrollTo({
            left: cardLeft - (wrapperWidth / 2) + (cardWidth / 2),
            behavior: 'smooth'
          });
        }
      }
    };

    thumbCards.forEach((card, idx) => {
      card.addEventListener('click', () => {
        activateShowcaseItem(idx);
        resetAutoAdvance();
      });
    });

    if (prevBtn) {
      prevBtn.addEventListener('click', () => {
        activateShowcaseItem(currentIndex - 1);
        resetAutoAdvance();
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener('click', () => {
        activateShowcaseItem(currentIndex + 1);
        resetAutoAdvance();
      });
    }

    const startAutoAdvance = () => {
      autoTimer = setInterval(() => {
        activateShowcaseItem(currentIndex + 1);
      }, 6000);
    };

    const resetAutoAdvance = () => {
      if (autoTimer) clearInterval(autoTimer);
      startAutoAdvance();
    };

    startAutoAdvance();

    // Category Filter Tabs
    filterPillBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        filterPillBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        const filterValue = btn.getAttribute('data-filter');
        thumbCards.forEach(card => {
          const cardCat = card.getAttribute('data-category');
          if (filterValue === 'all' || cardCat === filterValue) {
            card.style.display = 'block';
          } else {
            card.style.display = 'none';
          }
        });

        // Activate first visible thumb card
        const visibleCard = thumbCards.find(card => card.style.display !== 'none');
        if (visibleCard) {
          const newIdx = parseInt(visibleCard.getAttribute('data-index'), 10);
          activateShowcaseItem(newIdx);
        }
      });
    });
  }

  // 8. Production BTS Lightbox Modal
  const btsCards = document.querySelectorAll('.trigger-lightbox');
  const btsModal = document.getElementById('btsLightbox');
  const btsImg = document.getElementById('btsLightboxImg');
  const btsCaption = document.getElementById('btsLightboxCaption');
  const btsClose = document.getElementById('btsLightboxClose');
  const btsOverlay = document.querySelector('.bts-lightbox-overlay');

  if (btsCards.length > 0 && btsModal) {
    btsCards.forEach(card => {
      card.addEventListener('click', () => {
        const imgSrc = card.getAttribute('data-img');
        const caption = card.getAttribute('data-caption');
        if (btsImg) btsImg.src = imgSrc;
        if (btsCaption) btsCaption.textContent = caption;
        btsModal.classList.add('active');
        document.body.style.overflow = 'hidden';
      });
    });

    const closeModal = () => {
      btsModal.classList.remove('active');
      document.body.style.overflow = '';
    };

    if (btsClose) btsClose.addEventListener('click', closeModal);
    if (btsOverlay) btsOverlay.addEventListener('click', closeModal);

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && btsModal.classList.contains('active')) {
        closeModal();
      }
    });
  }

  // 9. Discovery Form Character Counter
  const projectDesc = document.getElementById('projectDesc');
  const charCount = document.getElementById('charCount');

  if (projectDesc && charCount) {
    projectDesc.addEventListener('input', () => {
      const currentLength = projectDesc.value.length;
      charCount.textContent = `${currentLength}/500`;
    });
  }

  // 10. Scroll To Top Button
  const scrollTopBtn = document.getElementById('scrollTopBtn');
  if (scrollTopBtn) {
    window.addEventListener('scroll', () => {
      if (window.scrollY > 400) {
        scrollTopBtn.classList.add('visible');
      } else {
        scrollTopBtn.classList.remove('visible');
      }
    });

    scrollTopBtn.addEventListener('click', () => {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });
  }

  // 11. Cookie Consent Banner & Preferences Modal Logic
  const cookieBanner = document.getElementById('cookieBanner');
  const cookieMainView = document.getElementById('cookieMainView');
  const cookiePrefsView = document.getElementById('cookiePrefsView');
  const acceptCookiesBtn = document.getElementById('acceptCookiesBtn');
  const rejectCookiesBtn = document.getElementById('rejectCookiesBtn');
  const manageCookiesBtn = document.getElementById('manageCookiesBtn');
  const cookieBackBtn = document.getElementById('cookieBackBtn');
  const saveCookiesBtn = document.getElementById('saveCookiesBtn');

  if (cookieBanner) {
    const consentState = localStorage.getItem('falhen_cookie_consent');
    if (!consentState) {
      setTimeout(() => {
        cookieBanner.classList.add('active');
      }, 1000);
    }

    const hideBanner = (decision) => {
      localStorage.setItem('falhen_cookie_consent', decision);
      cookieBanner.classList.remove('active');
    };

    if (acceptCookiesBtn) {
      acceptCookiesBtn.addEventListener('click', () => hideBanner('accepted'));
    }

    if (rejectCookiesBtn) {
      rejectCookiesBtn.addEventListener('click', () => hideBanner('rejected'));
    }

    if (manageCookiesBtn) {
      manageCookiesBtn.addEventListener('click', (e) => {
        e.preventDefault();
        if (cookieMainView && cookiePrefsView) {
          cookieMainView.classList.remove('active');
          cookiePrefsView.classList.add('active');
        }
      });
    }

    if (cookieBackBtn) {
      cookieBackBtn.addEventListener('click', () => {
        if (cookieMainView && cookiePrefsView) {
          cookiePrefsView.classList.remove('active');
          cookieMainView.classList.add('active');
        }
      });
    }

    if (saveCookiesBtn) {
      saveCookiesBtn.addEventListener('click', () => hideBanner('custom'));
    }
  }

  // Testimonials Carousel Handler
  const testiWrapper = document.querySelector('.testimonials-wrapper');
  const testiGrid = document.getElementById('testimonials-grid');
  const testiPrevBtn = document.getElementById('testi-prev-btn');
  const testiNextBtn = document.getElementById('testi-next-btn');
  const testiDots = document.querySelectorAll('.testimonial-dots .dot');
  const testiCards = document.querySelectorAll('.testimonial-card');

  if (testiWrapper && testiCards.length > 0) {
    let currentTestiIndex = 0; // Default to first testimonial card

    const activateTestimonial = (index) => {
      if (index < 0) index = testiCards.length - 1;
      if (index >= testiCards.length) index = 0;
      currentTestiIndex = index;

      testiCards.forEach((card, idx) => {
        if (idx === currentTestiIndex) {
          card.classList.add('featured-card', 'active');
        } else {
          card.classList.remove('featured-card', 'active');
        }
      });

      testiDots.forEach((dot, idx) => {
        if (idx === currentTestiIndex) {
          dot.classList.add('active');
        } else {
          dot.classList.remove('active');
        }
      });

      const activeCard = testiCards[currentTestiIndex];
      if (activeCard) {
        let targetLeft = 0;
        if (currentTestiIndex === 0) {
          targetLeft = 0;
        } else if (currentTestiIndex >= testiCards.length - 1) {
          targetLeft = testiWrapper.scrollWidth - testiWrapper.clientWidth;
        } else {
          targetLeft = activeCard.offsetLeft;
        }

        testiWrapper.scrollTo({
          left: targetLeft,
          behavior: 'smooth'
        });
      }
    };

    if (testiPrevBtn) {
      testiPrevBtn.addEventListener('click', () => {
        activateTestimonial(currentTestiIndex - 1);
      });
    }

    if (testiNextBtn) {
      testiNextBtn.addEventListener('click', () => {
        activateTestimonial(currentTestiIndex + 1);
      });
    }

    testiDots.forEach((dot, idx) => {
      dot.addEventListener('click', () => {
        activateTestimonial(idx);
      });
    });

    testiCards.forEach((card, idx) => {
      card.addEventListener('click', () => {
        activateTestimonial(idx);
      });
    });

    testiWrapper.addEventListener('scroll', () => {
      if (window.innerWidth <= 768) {
        const scrollPos = testiWrapper.scrollLeft;
        const cardWidth = testiWrapper.clientWidth;
        if (cardWidth > 0) {
          const newIndex = Math.round(scrollPos / cardWidth);
          if (newIndex !== currentTestiIndex && newIndex >= 0 && newIndex < testiCards.length) {
            currentTestiIndex = newIndex;
            testiCards.forEach((card, idx) => {
              card.classList.toggle('active', idx === currentTestiIndex);
            });
            testiDots.forEach((dot, idx) => {
              dot.classList.toggle('active', idx === currentTestiIndex);
            });
          }
        }
      }
    });
  }

  // 12. Mobile Services Swiping Carousel Handler
  const servicesGrid = document.querySelector('.services-grid');
  const servicesPrevBtn = document.getElementById('servicesPrevBtn');
  const servicesNextBtn = document.getElementById('servicesNextBtn');
  const servicesDots = document.querySelectorAll('.services-dot');
  const serviceCards = document.querySelectorAll('.services-grid .service-card');

  if (servicesGrid && serviceCards.length > 0) {
    let activeServiceIndex = 0;

    const updateActiveServicesDot = (index) => {
      if (index < 0) index = 0;
      if (index >= serviceCards.length) index = serviceCards.length - 1;
      activeServiceIndex = index;

      servicesDots.forEach((dot, idx) => {
        if (idx === activeServiceIndex) {
          dot.classList.add('active');
        } else {
          dot.classList.remove('active');
        }
      });
    };

    const scrollToServiceCard = (index) => {
      if (index < 0) index = serviceCards.length - 1;
      if (index >= serviceCards.length) index = 0;
      const targetCard = serviceCards[index];
      if (targetCard && servicesGrid) {
        servicesGrid.scrollTo({
          left: targetCard.offsetLeft,
          behavior: 'smooth'
        });
        updateActiveServicesDot(index);
      }
    };

    if (servicesPrevBtn) {
      servicesPrevBtn.addEventListener('click', () => {
        scrollToServiceCard(activeServiceIndex - 1);
      });
    }

    if (servicesNextBtn) {
      servicesNextBtn.addEventListener('click', () => {
        scrollToServiceCard(activeServiceIndex + 1);
      });
    }

    servicesDots.forEach((dot, idx) => {
      dot.addEventListener('click', () => {
        scrollToServiceCard(idx);
      });
    });

    servicesGrid.addEventListener('scroll', () => {
      if (window.innerWidth <= 768) {
        const scrollPos = servicesGrid.scrollLeft;
        const cardWidth = servicesGrid.clientWidth;
        if (cardWidth > 0) {
          const newIndex = Math.round(scrollPos / cardWidth);
          updateActiveServicesDot(newIndex);
        }
      }
    });
  }

  // 13. Mobile Team Swiping Carousel Handler
  const teamGrid = document.querySelector('.team-grid');
  const teamPrevBtn = document.getElementById('teamPrevBtn');
  const teamNextBtn = document.getElementById('teamNextBtn');
  const teamDots = document.querySelectorAll('.team-dot');
  const teamCards = document.querySelectorAll('.team-grid .team-card');

  if (teamGrid && teamCards.length > 0) {
    let activeTeamIndex = 0;

    const updateActiveTeamDot = (index) => {
      if (index < 0) index = 0;
      if (index >= teamCards.length) index = teamCards.length - 1;
      activeTeamIndex = index;

      teamDots.forEach((dot, idx) => {
        dot.classList.toggle('active', idx === activeTeamIndex);
      });
    };

    const scrollToTeamCard = (index) => {
      if (index < 0) index = teamCards.length - 1;
      if (index >= teamCards.length) index = 0;
      const targetCard = teamCards[index];
      if (targetCard && teamGrid) {
        teamGrid.scrollTo({
          left: targetCard.offsetLeft,
          behavior: 'smooth'
        });
        updateActiveTeamDot(index);
      }
    };

    if (teamPrevBtn) {
      teamPrevBtn.addEventListener('click', () => {
        scrollToTeamCard(activeTeamIndex - 1);
      });
    }

    if (teamNextBtn) {
      teamNextBtn.addEventListener('click', () => {
        scrollToTeamCard(activeTeamIndex + 1);
      });
    }

    teamDots.forEach((dot, idx) => {
      dot.addEventListener('click', () => {
        scrollToTeamCard(idx);
      });
    });

    teamGrid.addEventListener('scroll', () => {
      if (window.innerWidth <= 768) {
        const scrollPos = teamGrid.scrollLeft;
        const cardWidth = teamGrid.clientWidth;
        if (cardWidth > 0) {
          const newIndex = Math.round(scrollPos / cardWidth);
          updateActiveTeamDot(newIndex);
        }
      }
    });
  }

  // 14. Mobile Bottom Dock Scroll Top Button
  const dockScrollTop = document.getElementById('dockScrollTop');
  if (dockScrollTop) {
    dockScrollTop.addEventListener('click', () => {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });
  }
});
