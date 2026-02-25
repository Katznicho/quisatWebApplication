import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ActivityIndicator,
  ScrollView,
} from 'react-native';
import { createStudentProgressApi } from '../api/studentProgress';

// Parent view: Student Progress screen that includes
// the character_program block from the API.
//
// Expected props:
// - studentId
// - childName
// - baseUrl
// - token (parent auth token)

export function ParentStudentProgressScreen({
  studentId,
  childName,
  baseUrl,
  token,
}) {
  const api = createStudentProgressApi({ baseUrl, token });

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [progress, setProgress] = useState(null);

  useEffect(() => {
    let mounted = true;
    (async () => {
      try {
        const data = await api.getProgress(studentId);
        if (!mounted) return;
        setProgress(data);
      } catch (e) {
        console.warn('Failed to load student progress', e);
        if (mounted) setError('Failed to load progress.');
      } finally {
        if (mounted) setLoading(false);
      }
    })();
    return () => {
      mounted = false;
    };
  }, [studentId]);

  if (loading) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator />
      </View>
    );
  }

  if (error || !progress) {
    return (
      <View style={styles.centered}>
        <Text style={styles.errorText}>{error || 'No data available.'}</Text>
      </View>
    );
  }

  const overview = progress.overview || {};
  const character = progress.character_program || null;

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.content}>
      <Text style={styles.title}>{childName}</Text>

      {/* Overall header banner */}
      <View style={styles.banner}>
        <Text style={styles.bannerLabel}>Overall Progress</Text>
        <Text style={styles.bannerValue}>
          {overview.overall_progress || 'Not yet set'}
        </Text>
      </View>

      {/* Overview cards */}
      <View style={styles.row}>
        <View style={styles.card}>
          <Text style={styles.cardLabel}>Academic Average</Text>
          <Text style={styles.cardValue}>
            {overview.academic_average != null
              ? `${overview.academic_average.toFixed(1)}%`
              : 'N/A'}
          </Text>
        </View>
        <View style={styles.card}>
          <Text style={styles.cardLabel}>Attendance</Text>
          <Text style={styles.cardValue}>
            {overview.attendance != null
              ? `${overview.attendance.toFixed(1)}%`
              : 'N/A'}
          </Text>
        </View>
      </View>

      {/* Character program card */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Character Program</Text>
        <View style={styles.cardFull}>
          <Text style={styles.cardLabelSmall}>Status</Text>
          <Text style={styles.cardValue}>
            {character?.status || overview.overall_progress || 'Not yet set'}
          </Text>

          {character?.headline ? (
            <>
              <Text style={[styles.cardLabelSmall, { marginTop: 8 }]}>
                Summary
              </Text>
              <Text style={styles.bodyText}>{character.headline}</Text>
            </>
          ) : null}

          {character?.notes ? (
            <>
              <Text style={[styles.cardLabelSmall, { marginTop: 8 }]}>
                Details
              </Text>
              <Text style={styles.bodyText}>{character.notes}</Text>
            </>
          ) : null}

          {Array.isArray(character?.traits) && character.traits.length > 0 ? (
            <>
              <Text style={[styles.cardLabelSmall, { marginTop: 12 }]}>
                Highlights
              </Text>
              {character.traits.map((t) => (
                <View key={t.key} style={styles.traitRow}>
                  <Text style={styles.traitLabel}>{t.label || t.key}</Text>
                  <Text style={styles.traitValue}>{t.rating || ''}</Text>
                </View>
              ))}
            </>
          ) : null}
        </View>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F5F7FB',
  },
  content: {
    paddingHorizontal: 16,
    paddingVertical: 20,
  },
  centered: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  title: {
    fontSize: 22,
    fontWeight: '700',
    color: '#111827',
    marginBottom: 16,
  },
  errorText: {
    color: '#EF4444',
    fontSize: 14,
  },
  banner: {
    backgroundColor: '#ECF3FF',
    borderRadius: 12,
    padding: 14,
    marginBottom: 16,
  },
  bannerLabel: {
    fontSize: 13,
    color: '#4B5563',
  },
  bannerValue: {
    fontSize: 18,
    fontWeight: '700',
    color: '#011478',
    marginTop: 4,
  },
  row: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 16,
  },
  card: {
    flex: 1,
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 12,
    marginRight: 8,
    shadowColor: '#000',
    shadowOpacity: 0.04,
    shadowRadius: 4,
    shadowOffset: { width: 0, height: 2 },
    elevation: 1,
  },
  cardLabel: {
    fontSize: 13,
    color: '#6B7280',
  },
  cardValue: {
    fontSize: 16,
    fontWeight: '600',
    marginTop: 4,
    color: '#111827',
  },
  section: {
    marginTop: 8,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: '600',
    marginBottom: 8,
    color: '#111827',
  },
  cardFull: {
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 14,
    shadowColor: '#000',
    shadowOpacity: 0.04,
    shadowRadius: 4,
    shadowOffset: { width: 0, height: 2 },
    elevation: 1,
  },
  cardLabelSmall: {
    fontSize: 12,
    color: '#6B7280',
  },
  bodyText: {
    fontSize: 14,
    color: '#4B5563',
    marginTop: 2,
  },
  traitRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 6,
  },
  traitLabel: {
    fontSize: 14,
    color: '#111827',
  },
  traitValue: {
    fontSize: 14,
    fontWeight: '600',
    color: '#111827',
  },
});

